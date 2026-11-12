<?php

namespace App\Http\Controllers\BendaharaMini;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\PeriodeLockService;
use App\Services\PinjamanCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AngsuranBbmController extends Controller
{
    /**
     * Halaman penerimaan angsuran pembayaran langsung (tunai di loket Bank Mini).
     * Dikelompokkan per paket pinjaman aktif pegawai agar terstruktur dan mudah dipahami.
     */
    public function catatLangsung()
    {
        // Ambil seluruh pinjaman aktif yang sedang berjalan (status cair)
        $pinjamanAktifList = DB::table('tblPINJAMAN as pin')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->where('pin.status_pinjaman', 'cair')
            ->select(
                'pin.*',
                'n.nama_nasabah',
                'n.kelas_unit',
                'n.no_hp'
            )
            ->orderBy('pin.pinjaman_id', 'asc')
            ->get();

        // Hubungkan setiap paket pinjaman dengan jadwal angsuran dan tagihan terdekat
        foreach ($pinjamanAktifList as $p) {
            $jadwals = DB::table('tblJADWAL_ANGSURAN')
                ->where('pinjaman_id', $p->pinjaman_id)
                ->orderBy('angsuran_ke', 'asc')
                ->get();
            $p->jadwals = $jadwals;
            $p->tagihanBerikutnya = $jadwals->firstWhere('status_bayar', 'belum');
            $p->sisaBelumBayar = $jadwals->where('status_bayar', 'belum')->count();
            $p->sudahBayar = $jadwals->where('status_bayar', 'lunas')->count();
        }

        // Riwayat angsuran tunai yang sah/aktif (dipaginasi 10 per halaman)
        $riwayatLangsung = DB::table('tblANGSURAN as ang')
            ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
            ->whereNull('ang.detail_potongan_id') // Pembayaran langsung (bukan potongan tunjangan)
            ->whereNull('kor.koreksi_id')         // Hanya tampilkan transaksi sah yang belum pernah dikoreksi
            ->select(
                'ang.*',
                'ja.angsuran_ke',
                'n.nama_nasabah',
                'n.kelas_unit'
            )
            ->orderByDesc('ang.angsuran_id')
            ->paginate(10);

        return view('bendahara_mini.angsuran.langsung', compact('pinjamanAktifList', 'riwayatLangsung'));
    }

    /**
     * Simpan transaksi pembayaran angsuran langsung.
     */
    public function simpanPembayaranLangsung(Request $request)
    {
        $request->validate([
            'jadwal_angsuran_id' => 'required|exists:tblJADWAL_ANGSURAN,jadwal_angsuran_id',
            'tgl_bayar'          => 'required|date',
            'bayar_pokok'        => 'required|numeric|min:1',
            'bayar_bunga'        => 'required|numeric|min:0',
        ], [
            'jadwal_angsuran_id.required' => 'Tagihan angsuran wajib dipilih.',
            'tgl_bayar.required'          => 'Tanggal pembayaran wajib diisi.',
            'bayar_pokok.min'             => 'Nominal pokok bayar minimal Rp 1.',
        ]);

        $jadwal = DB::table('tblJADWAL_ANGSURAN')->where('jadwal_angsuran_id', $request->jadwal_angsuran_id)->first();
        if (!$jadwal || $jadwal->status_bayar === 'lunas') {
            return back()->with('error', 'Tagihan angsuran ini sudah lunas atau tidak valid.');
        }

        // Validasi urutan angsuran: Tidak boleh melompati angsuran sebelumnya yang belum lunas
        $angsuranSebelumnyaBelumLunas = DB::table('tblJADWAL_ANGSURAN')
            ->where('pinjaman_id', $jadwal->pinjaman_id)
            ->where('angsuran_ke', '<', $jadwal->angsuran_ke)
            ->where('status_bayar', '!=', 'lunas')
            ->orderBy('angsuran_ke', 'asc')
            ->first();

        if ($angsuranSebelumnyaBelumLunas) {
            return back()->with('error', "Pembayaran harus berurutan. Angsuran ke-{$angsuranSebelumnyaBelumLunas->angsuran_ke} belum dilunasi, silakan selesaikan tagihan sebelumnya terlebih dahulu.");
        }

        // Cek penguncian periode
        try {
            $periode = PeriodeLockService::assertPeriodeTerbuka($request->tgl_bayar);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $kasTunai = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();
        if (!$kasTunai) {
            return back()->with('error', 'Master Kas Tunai tidak ditemukan.');
        }

        $totalBayar = $request->bayar_pokok + $request->bayar_bunga;

        DB::transaction(function () use ($jadwal, $request, $periode, $kasTunai, $totalBayar) {
            // 1. Simpan baris angsuran
                    // 1. Simpan baris angsuran
        $angsuranId = DB::table('tblANGSURAN')
            ->insertGetId([


                'jadwal_angsuran_id' => $jadwal->jadwal_angsuran_id,
                'detail_potongan_id' => null,
                'user_id'            => auth()->id(),
                'tgl_bayar'          => $request->tgl_bayar,
                'jenis_bayar'        => 'angsuran',
                'bayar_pokok'        => $request->bayar_pokok,
                'bayar_bunga'        => $request->bayar_bunga,
            ], 'angsuran_id');

            // 2. Update status jadwal menjadi lunas
            DB::table('tblJADWAL_ANGSURAN')
                ->where('jadwal_angsuran_id', $jadwal->jadwal_angsuran_id)
                ->update(['status_bayar' => 'lunas']);

            // 3. Catat mutasi kas masuk
            DB::table('tblMUTASI_KAS')->insert([
                'kas_id'       => $kasTunai->kas_id,
                'periode_id'   => $periode->periode_id,
                'user_id'      => auth()->id(),
                'transaksi_id' => null,
                'angsuran_id'  => $angsuranId,
                'pencairan_id' => null,
                'tgl_mutasi'   => $request->tgl_bayar,
                'arah'         => 'masuk',
                'nominal'      => $totalBayar,
                'uraian'       => "Penerimaan angsuran langsung ke-{$jadwal->angsuran_ke} pinjaman #PINJ-{$jadwal->pinjaman_id}",
            ]);

            // 4. Update saldo kas tunai bertambah
            DB::table('tblKAS')->where('kas_id', $kasTunai->kas_id)->increment('saldo', $totalBayar);

            // 5. Cek apakah seluruh jadwal pinjaman ini sudah lunas
            $sisaJadwalBelumBayar = DB::table('tblJADWAL_ANGSURAN')
                ->where('pinjaman_id', $jadwal->pinjaman_id)
                ->where('status_bayar', 'belum')
                ->count();

            if ($sisaJadwalBelumBayar === 0) {
                DB::table('tblPINJAMAN')
                    ->where('pinjaman_id', $jadwal->pinjaman_id)
                    ->update(['status_pinjaman' => 'lunas']);
            }

            AuditLogService::log(
                aksi: 'CATAT_ANGSURAN_LANGSUNG',
                entitas: 'tblANGSURAN',
                entitasId: $angsuranId,
                dataBaru: [
                    'jadwal_angsuran_id' => $jadwal->jadwal_angsuran_id,
                    'bayar_pokok'        => $request->bayar_pokok,
                    'bayar_bunga'        => $request->bayar_bunga,
                    'total_bayar'        => $totalBayar,
                ]
            );
        });

        return redirect()->route('bbm.angsuran.langsung')
            ->with('success', "Pembayaran angsuran ke-{$jadwal->angsuran_ke} sebesar Rp " . number_format($totalBayar, 0, ',', '.') . ' berhasil dicatat!');
    }

    /**
     * Halaman penyusunan rencana potongan bulanan per pegawai.
     */
    public function rencanaPotongan(Request $request)
    {
        $bulanSekarang = (int) $request->get('bulan', date('n'));
        $tahunSekarang = (int) $request->get('tahun', date('Y'));

        // Tagihan potongan yang jatuh tempo pada bulan ini dan belum bayar
        // serta belum dimasukkan ke dalam paket rencana potongan aktif
        $calonPotongan = DB::table('tblJADWAL_ANGSURAN as ja')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->where('pin.metode_bayar', 'potong_gaji')
            ->where('pin.status_pinjaman', 'cair')
            ->where('ja.status_bayar', 'belum')
            ->whereMonth('ja.jatuh_tempo', $bulanSekarang)
            ->whereYear('ja.jatuh_tempo', $tahunSekarang)
            ->whereNotIn('ja.jadwal_angsuran_id', function ($query) {
                $query->select('dp.jadwal_angsuran_id')
                    ->from('tblDETAIL_POTONGAN as dp')
                    ->join('tblRENCANA_POTONGAN as rp', 'dp.rencana_potongan_id', '=', 'rp.rencana_potongan_id')
                    ->where(function ($w) {
                        $w->whereIn('rp.status_potongan', ['dikirim', 'diterima', 'menunggu_verifikasi'])
                          ->orWhere(function ($q) {
                              $q->where('rp.status_potongan', 'selesai')
                                ->where('dp.hasil_potongan', 'berhasil');
                          });
                    });
            })
            // Pastikan pembayaran berurutan: Tidak boleh memotong cicilan ke-N jika cicilan sebelumnya belum lunas
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tblJADWAL_ANGSURAN as ja_prev')
                    ->whereColumn('ja_prev.pinjaman_id', 'ja.pinjaman_id')
                    ->whereColumn('ja_prev.angsuran_ke', '<', 'ja.angsuran_ke')
                    ->where('ja_prev.status_bayar', '!=', 'lunas');
            })
            ->select(
                'ja.*',
                'pin.nominal_pinjaman',
                'pin.tenor',
                'n.nama_nasabah',
                'n.kelas_unit',
                DB::raw('(ja.pokok + ja.bunga) as total_angsuran')
            )
            ->orderBy('n.nama_nasabah', 'asc')
            ->get();

        // Riwayat paket rencana potongan dengan filter bulan, tahun, dan status verifikasi
        $filterBulan = $request->query('filter_bulan');
        $filterTahun = $request->query('filter_tahun');
        $filterStatus = $request->query('filter_status');

        $riwayatQuery = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->select('rp.*', 'p.bulan', 'p.tahun');

        if (!empty($filterBulan)) {
            $riwayatQuery->where('p.bulan', $filterBulan);
        }
        if (!empty($filterTahun)) {
            $riwayatQuery->where('p.tahun', $filterTahun);
        }
        if (!empty($filterStatus)) {
            $riwayatQuery->where('rp.status_potongan', $filterStatus);
        }

        $riwayatRencana = $riwayatQuery->orderByDesc('rp.rencana_potongan_id')
            ->paginate(10)
            ->withQueryString();

        // Ambil rincian detail per paket untuk In-Page Detail Banner
        $rencanaIds = $riwayatRencana->pluck('rencana_potongan_id');
        $rincianPerPaket = DB::table('tblDETAIL_POTONGAN as dp')
            ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->whereIn('dp.rencana_potongan_id', $rencanaIds)
            ->select(
                'dp.*',
                'ja.angsuran_ke',
                'ja.jatuh_tempo',
                'ja.pokok',
                'ja.bunga',
                'ja.status_bayar as status_angsuran',
                'pin.tenor',
                'n.nama_nasabah',
                'n.kelas_unit',
                'n.gaji_pokok',
                'n.tunjangan'
            )
            ->orderBy('n.nama_nasabah')
            ->get()
            ->groupBy('rencana_potongan_id');

        $kasList = DB::table('tblKAS')->get();

        return view('bendahara_mini.angsuran.potongan', compact(
            'calonPotongan',
            'riwayatRencana',
            'bulanSekarang',
            'tahunSekarang',
            'rincianPerPaket',
            'kasList'
        ));
    }

    /**
     * Simpan paket rencana potongan angsuran bulanan.
     */
    public function simpanRencanaPotongan(Request $request)
    {
        $request->validate([
            'jadwal_ids' => 'required|array|min:1',
            'bulan'      => 'required|integer|min:1|max:12',
            'tahun'      => 'required|integer|min:2020|max:2035',
        ], [
            'jadwal_ids.required' => 'Pilih minimal satu pegawai untuk dimasukkan dalam rencana potongan.',
        ]);

        $tanggalReferensi = Carbon::createFromDate($request->tahun, $request->bulan, 1)->toDateString();
        try {
            $periode = PeriodeLockService::assertPeriodeTerbuka($tanggalReferensi);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Ambil data jadwal terpilih
        $jadwals = DB::table('tblJADWAL_ANGSURAN')
            ->whereIn('jadwal_angsuran_id', $request->jadwal_ids)
            ->where('status_bayar', 'belum')
            ->get();

        if ($jadwals->isEmpty()) {
            return back()->with('error', 'Tidak ada jadwal tagihan valid yang dapat diproses.');
        }

        // Validasi urutan: Pastikan tidak ada angsuran terdahulu yang terlewat
        foreach ($jadwals as $j) {
            $prevUnpaid = DB::table('tblJADWAL_ANGSURAN')
                ->where('pinjaman_id', $j->pinjaman_id)
                ->where('angsuran_ke', '<', $j->angsuran_ke)
                ->where('status_bayar', '!=', 'lunas')
                ->first();

            if ($prevUnpaid) {
                return back()->with('error', "Rencana potongan harus berurutan. Angsuran ke-{$prevUnpaid->angsuran_ke} dari salah satu pinjaman belum lunas.");
            }
        }

        $totalRencana = $jadwals->sum(fn ($j) => $j->pokok + $j->bunga);

        DB::transaction(function () use ($jadwals, $periode, $totalRencana) {
            $rencanaId = DB::table('tblRENCANA_POTONGAN')->insertGetId([
                'periode_id'      => $periode->periode_id,
                'user_id'         => auth()->id(),
                'total_potongan'  => $totalRencana,
                'status_potongan' => 'dikirim',
            ], 'rencana_potongan_id');

            foreach ($jadwals as $j) {
                DB::table('tblDETAIL_POTONGAN')->insert([
                    'rencana_potongan_id' => $rencanaId,
                    'jadwal_angsuran_id'  => $j->jadwal_angsuran_id,
                    'nominal_rencana'     => $j->pokok + $j->bunga,
                    'hasil_potongan'      => null,
                ]);
            }

            AuditLogService::log(
                aksi: 'BUAT_RENCANA_POTONGAN',
                entitas: 'tblRENCANA_POTONGAN',
                entitasId: $rencanaId,
                dataBaru: [
                    'periode_id'     => $periode->periode_id,
                    'total_potongan' => $totalRencana,
                    'jumlah_pegawai' => $jadwals->count(),
                ]
            );
        });

        return redirect()->route('bbm.angsuran.potongan')
            ->with('success', 'Rencana potongan bulanan berhasil disusun dan otomatis diteruskan ke Bendahara Sekolah!');
    }

    /**
     * Detail paket rencana potongan bulanan & konfirmasi penerimaan dana dari Bendahara Sekolah.
     */
    public function detailRencanaPotongan($rencanaId)
    {
        $rencana = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->join('tblUSER as u', 'rp.user_id', '=', 'u.user_id')
            ->where('rp.rencana_potongan_id', $rencanaId)
            ->select('rp.*', 'p.bulan', 'p.tahun', 'u.nama_user as nama_pembuat')
            ->first();

        if (!$rencana) {
            abort(404, 'Data rencana potongan tidak ditemukan.');
        }

        $detailList = DB::table('tblDETAIL_POTONGAN as dp')
            ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->where('dp.rencana_potongan_id', $rencanaId)
            ->select(
                'dp.*',
                'ja.angsuran_ke',
                'ja.jatuh_tempo',
                'ja.pokok',
                'ja.bunga',
                'ja.status_bayar',
                'pin.pinjaman_id',
                'n.nama_nasabah',
                'n.kelas_unit',
                'n.gaji_pokok',
                'n.tunjangan'
            )
            ->orderBy('n.nama_nasabah')
            ->get();

        $kasList = DB::table('tblKAS')->get();

        return view('bendahara_mini.angsuran.potongan_detail', compact('rencana', 'detailList', 'kasList'));
    }

    /**
     * Konfirmasi penerimaan dana fisik/transfer dari Bendahara Sekolah.
     * Uang masuk ke kas Bank Mini dan otomatis membukukan angsuran seluruh pegawai yang berhasil dipotong.
     */
    public function konfirmasiPenerimaanDana(Request $request, $rencanaId)
    {
        $request->validate([
            'kas_tujuan' => 'nullable|in:kas_harian,kas_bank',
            'tgl_terima' => 'required|date',
            'catatan'    => 'nullable|string|max:500',
        ], [
            'tgl_terima.required' => 'Tanggal serah terima/penerimaan uang wajib diisi.',
        ]);

        $rencana = DB::table('tblRENCANA_POTONGAN')->where('rencana_potongan_id', $rencanaId)->first();
        if (!$rencana) {
            return back()->with('error', 'Rencana potongan tidak ditemukan.');
        }

        if ($rencana->status_potongan !== 'menunggu_verifikasi') {
            return back()->with('error', 'Paket ini tidak dalam status menunggu verifikasi dana.');
        }

        try {
            $periode = PeriodeLockService::assertPeriodeTerbuka($request->tgl_terima);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Tentukan master kas penampung:
        // Otomatis 100% mendeteksi dari metode penyerahan Bendahara Sekolah (Opsi A)
        if ($request->filled('kas_tujuan')) {
            $jenisKas = ($request->kas_tujuan === 'kas_harian') ? 'kas' : 'bank';
        } else {
            $isTransfer = str_contains(strtolower($rencana->metode_penyerahan ?? ''), 'transfer');
            $jenisKas = $isTransfer ? 'bank' : 'kas';
        }

        $kas = DB::table('tblKAS')->where('jenis_kas', $jenisKas)->first();
        if (!$kas) {
            $kas = DB::table('tblKAS')->first();
        }
        if (!$kas) {
            return back()->with('error', "Master Kas penampung tidak ditemukan.");
        }

        DB::transaction(function () use ($request, $rencanaId, $rencana, $periode, $kas) {
            $detailSukses = DB::table('tblDETAIL_POTONGAN as dp')
                ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
                ->where('dp.rencana_potongan_id', $rencanaId)
                ->where('dp.hasil_potongan', 'berhasil')
                ->select('dp.*', 'ja.pokok', 'ja.bunga', 'ja.pinjaman_id', 'ja.status_bayar')
                ->get();

            $totalDibukukan = 0;

            foreach ($detailSukses as $detail) {
                if ($detail->status_bayar === 'lunas') {
                    continue; // Skip jika sudah lunas
                }

                // 1. Catat baris angsuran resmi
                        // 1. Simpan baris angsuran
        $angsuranId = DB::table('tblANGSURAN')
            ->insertGetId([


                    'jadwal_angsuran_id' => $detail->jadwal_angsuran_id,
                    'detail_potongan_id' => $detail->detail_potongan_id,
                    'user_id'            => auth()->id(),
                    'tgl_bayar'          => $request->tgl_terima,
                    'jenis_bayar'        => 'angsuran',
                    'bayar_pokok'        => $detail->pokok,
                    'bayar_bunga'        => $detail->bunga,
                ], 'angsuran_id');

                // 2. Tandai jadwal angsuran lunas
                DB::table('tblJADWAL_ANGSURAN')
                    ->where('jadwal_angsuran_id', $detail->jadwal_angsuran_id)
                    ->update(['status_bayar' => 'lunas']);

                $totalDibukukan += ($detail->pokok + $detail->bunga);

                // 3. Cek apakah seluruh jadwal pinjaman ini sudah lunas
                $sisaBelumBayar = DB::table('tblJADWAL_ANGSURAN')
                    ->where('pinjaman_id', $detail->pinjaman_id)
                    ->where('status_bayar', 'belum')
                    ->count();

                if ($sisaBelumBayar === 0) {
                    DB::table('tblPINJAMAN')
                        ->where('pinjaman_id', $detail->pinjaman_id)
                        ->update(['status_pinjaman' => 'lunas']);
                }
            }

            // 4. Catat mutasi kas masuk penampung hasil potongan dari Bendahara Sekolah
            $nominalMasuk = (float) $rencana->total_realisasi;
            if ($nominalMasuk > 0) {
                DB::table('tblMUTASI_KAS')->insert([
                    'kas_id'       => $kas->kas_id,
                    'periode_id'   => $periode->periode_id,
                    'user_id'      => auth()->id(),
                    'transaksi_id' => null,
                    'angsuran_id'  => null,
                    'pencairan_id' => null,
                    'tgl_mutasi'   => $request->tgl_terima,
                    'arah'         => 'masuk',
                    'nominal'      => $nominalMasuk,
                    'uraian'       => "Penerimaan hasil potongan gaji paket #RP-{$rencanaId} dari Bendahara Sekolah (" . strtoupper($rencana->metode_penyerahan ?? 'tunai') . ")",
                ]);

                // Update saldo master kas bertambah
                DB::table('tblKAS')->where('kas_id', $kas->kas_id)->increment('saldo', $nominalMasuk);
            }

            // 5. Update status rencana potongan menjadi 'selesai'
            DB::table('tblRENCANA_POTONGAN')
                ->where('rencana_potongan_id', $rencanaId)
                ->update(['status_potongan' => 'selesai']);

            AuditLogService::log(
                aksi: 'TERIMA_DANA_POTONGAN_SEKOLAH',
                entitas: 'tblRENCANA_POTONGAN',
                entitasId: $rencanaId,
                dataBaru: [
                    'tgl_terima'     => $request->tgl_terima,
                    'kas_tujuan'     => $request->kas_tujuan,
                    'total_diterima' => $nominalMasuk,
                    'status'         => 'selesai',
                ]
            );
        });

        return redirect()->route('bbm.angsuran.potongan')
            ->with('success', "Dana potongan sebesar Rp " . number_format($rencana->total_realisasi, 0, ',', '.') . " berhasil diterima dan dibukukan ke Kas Bank Mini! Seluruh angsuran pegawai terkait otomatis lunas.");
    }

    /**
     * Cetak Berita Acara Serah Terima (BAST) untuk Bendahara Bank Mini.
     */
    public function cetakBast($rencanaId)
    {
        $rencana = DB::table('tblRENCANA_POTONGAN as rp')
            ->join('tblPERIODE as p', 'rp.periode_id', '=', 'p.periode_id')
            ->join('tblUSER as u', 'rp.user_id', '=', 'u.user_id')
            ->where('rp.rencana_potongan_id', $rencanaId)
            ->select('rp.*', 'p.bulan', 'p.tahun', 'u.nama_user as nama_pembuat_bbm')
            ->first();

        if (!$rencana) {
            abort(404, 'Rencana potongan tidak ditemukan.');
        }

        $detailList = DB::table('tblDETAIL_POTONGAN as dp')
            ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->where('dp.rencana_potongan_id', $rencanaId)
            ->select(
                'dp.*',
                'ja.angsuran_ke',
                'ja.pokok',
                'ja.bunga',
                'n.nama_nasabah',
                'n.kelas_unit'
            )
            ->orderBy('n.nama_nasabah')
            ->get();

        return view('bendahara.potongan.bast', compact('rencana', 'detailList'));
    }

    /**
     * Halaman koreksi transaksi angsuran dikelompokkan berdasarkan Paket Pinjaman.
     */
    public function koreksi()
    {
        // Ambil paket-paket pinjaman yang memiliki minimal 1 transaksi angsuran
        $paketPinjamanList = DB::table('tblPINJAMAN as pin')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->select(
                'pin.pinjaman_id',
                'pin.nominal_pinjaman',
                'pin.tenor',
                'pin.metode_bayar',
                'pin.status_pinjaman',
                'n.nama_nasabah',
                'n.kelas_unit'
            )
            ->whereExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('tblJADWAL_ANGSURAN as ja')
                  ->join('tblANGSURAN as ang', 'ja.jadwal_angsuran_id', '=', 'ang.jadwal_angsuran_id')
                  ->whereColumn('ja.pinjaman_id', 'pin.pinjaman_id');
            })
            ->orderByDesc('pin.pinjaman_id')
            ->get();

        foreach ($paketPinjamanList as $p) {
            $transaksi = DB::table('tblANGSURAN as ang')
                ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
                ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
                ->where('ja.pinjaman_id', $p->pinjaman_id)
                ->select(
                    'ang.*',
                    'ja.angsuran_ke',
                    'ja.jatuh_tempo',
                    'kor.koreksi_id',
                    'kor.alasan_koreksi',
                    'kor.created_at as tgl_koreksi'
                )
                ->orderBy('ja.angsuran_ke')
                ->get();

            $p->daftar_transaksi = $transaksi;
            $p->total_transaksi = $transaksi->count();
            $p->transaksi_aktif = $transaksi->whereNull('koreksi_id')->count();
            $p->total_dana_masuk = $transaksi->whereNull('koreksi_id')->sum(function($t) {
                return (float)$t->bayar_pokok + (float)$t->bayar_bunga;
            });
        }

        // Riwayat audit koreksi yang sudah pernah dilakukan (paginasi 10)
        $riwayatKoreksi = DB::table('tblKOREKSI_ANGSURAN as kor')
            ->join('tblANGSURAN as ang', 'kor.angsuran_id', '=', 'ang.angsuran_id')
            ->join('tblJADWAL_ANGSURAN as ja', 'ang.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->join('tblPINJAMAN as pin', 'ja.pinjaman_id', '=', 'pin.pinjaman_id')
            ->join('tblNASABAH as n', 'pin.nasabah_id', '=', 'n.nasabah_id')
            ->join('tblUSER as u', 'kor.user_id', '=', 'u.user_id')
            ->select(
                'kor.*',
                'ang.bayar_pokok',
                'ang.bayar_bunga',
                'ang.tgl_bayar',
                'ja.angsuran_ke',
                'pin.pinjaman_id',
                'n.nama_nasabah',
                'u.nama_user as nama_eksekutor'
            )
            ->orderByDesc('kor.koreksi_id')
            ->paginate(10);

        return view('bendahara_mini.angsuran.koreksi', compact('paketPinjamanList', 'riwayatKoreksi'));
    }

    /**
     * Eksekusi transaksi pembalik koreksi angsuran tanpa menghapus baris lama.
     */
    public function prosesKoreksi(Request $request, $angsuranId)
    {
        $request->validate([
            'alasan_koreksi' => 'required|string|min:5|max:255',
        ], [
            'alasan_koreksi.required' => 'Alasan koreksi transaksi wajib dicantumkan sebagai bukti audit.',
            'alasan_koreksi.min'      => 'Alasan koreksi minimal 5 karakter.',
        ]);

        $angsuran = DB::table('tblANGSURAN')->where('angsuran_id', $angsuranId)->first();
        if (!$angsuran) {
            return back()->with('error', 'Transaksi angsuran tidak ditemukan.');
        }

        // Cek apakah transaksi ini sudah pernah dikoreksi
        $sudahKoreksi = DB::table('tblKOREKSI_ANGSURAN')->where('angsuran_id', $angsuranId)->exists();
        if ($sudahKoreksi) {
            return back()->with('error', 'Transaksi angsuran ini sudah pernah dikoreksi sebelumnya.');
        }

        // Cek penguncian periode hari ini
        try {
            $periode = PeriodeLockService::assertPeriodeTerbuka(now()->toDateString());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $kasTunai = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();
        $totalNominal = $angsuran->bayar_pokok + $angsuran->bayar_bunga;

        DB::transaction(function () use ($angsuran, $request, $periode, $kasTunai, $totalNominal) {
            // 1. Catat berita acara koreksi
            $koreksiId = DB::table('tblKOREKSI_ANGSURAN')->insertGetId([
                'angsuran_id'    => $angsuran->angsuran_id,
                'user_id'        => auth()->id(),
                'tgl_koreksi'    => now()->toDateString(),
                'alasan_koreksi' => $request->alasan_koreksi,
                'nominal_pokok'  => $angsuran->bayar_pokok,
                'nominal_bunga'  => $angsuran->bayar_bunga,
                'nominal_total'  => $totalNominal,
                'status_koreksi' => 'selesai',
            ], 'koreksi_id');

            // 2. Kembalikan status jadwal angsuran menjadi 'belum'
            DB::table('tblJADWAL_ANGSURAN')
                ->where('jadwal_angsuran_id', $angsuran->jadwal_angsuran_id)
                ->update(['status_bayar' => 'belum']);

            // 3. Catat mutasi kas keluar pembalik (reversal)
            DB::table('tblMUTASI_KAS')->insert([
                'kas_id'       => $kasTunai->kas_id,
                'periode_id'   => $periode->periode_id,
                'user_id'      => auth()->id(),
                'transaksi_id' => null,
                'angsuran_id'  => $angsuran->angsuran_id,
                'pencairan_id' => null,
                'tgl_mutasi'   => now()->toDateString(),
                'arah'         => 'keluar',
                'nominal'      => $totalNominal,
                'uraian'       => "Koreksi/reversal angsuran #ANG-{$angsuran->angsuran_id}: {$request->alasan_koreksi}",
            ]);

            // 4. Sesuaikan saldo kas tunai (berkurang kembali sebesar uang yang dibatalkan)
            DB::table('tblKAS')->where('kas_id', $kasTunai->kas_id)->decrement('saldo', $totalNominal);

            // 5. Kembalikan status pinjaman menjadi 'cair' jika sebelumnya terlanjur 'lunas'
            $jadwal = DB::table('tblJADWAL_ANGSURAN')->where('jadwal_angsuran_id', $angsuran->jadwal_angsuran_id)->first();
            if ($jadwal) {
                DB::table('tblPINJAMAN')
                    ->where('pinjaman_id', $jadwal->pinjaman_id)
                    ->update(['status_pinjaman' => 'cair']);
            }

            AuditLogService::log(
                aksi: 'KOREKSI_ANGSURAN_REVERSAL',
                entitas: 'tblKOREKSI_ANGSURAN',
                entitasId: $koreksiId,
                dataBaru: [
                    'angsuran_id'    => $angsuran->angsuran_id,
                    'alasan_koreksi' => $request->alasan_koreksi,
                    'nominal_total'  => $totalNominal,
                ]
            );
        });

        return redirect()->route('bbm.angsuran.koreksi')
            ->with('success', 'Transaksi angsuran berhasil dikoreksi melalui transaksi pembalik penyeimbang kas.');
    }
}

