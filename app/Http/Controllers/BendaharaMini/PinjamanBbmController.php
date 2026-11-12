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

class PinjamanBbmController extends Controller
{
    /**
     * Tampilkan seluruh daftar pinjaman dengan opsi filter status.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = DB::table('tblPINJAMAN')
            ->join('tblNASABAH', 'tblPINJAMAN.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->select(
                'tblPINJAMAN.*',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.no_hp as nasabah_hp',
                'tblNASABAH.kelas_unit',
                'tblNASABAH.gaji_pokok',
                'tblNASABAH.tunjangan'
            )
            ->orderByDesc('tblPINJAMAN.pinjaman_id');

        if ($status && in_array($status, ['diajukan', 'layak', 'ditolak', 'cair', 'lunas'])) {
            $query->where('tblPINJAMAN.status_pinjaman', $status);
        }

        $pinjamanList = $query->paginate(15);

        // Ringkasan metrik
        $totalPengajuan = DB::table('tblPINJAMAN')->count();
        $totalLayak = DB::table('tblPINJAMAN')->where('status_pinjaman', 'layak')->count();
        $totalCair = DB::table('tblPINJAMAN')->where('status_pinjaman', 'cair')->count();
        $totalLunas = DB::table('tblPINJAMAN')->where('status_pinjaman', 'lunas')->count();

        return view('bendahara_mini.pinjaman.index', compact(
            'pinjamanList',
            'status',
            'totalPengajuan',
            'totalLayak',
            'totalCair',
            'totalLunas'
        ));
    }

    /**
     * Form pengajuan pinjaman baru khusus pegawai (Guru & Karyawan).
     */
    public function create()
    {
        // Hanya nasabah pegawai yang memiliki rekening tabungan AKTIF yang boleh meminjam
        $pegawaiList = DB::table('tblNASABAH')
            ->join('tblREKENING', 'tblNASABAH.nasabah_id', '=', 'tblREKENING.nasabah_id')
            ->where('tblNASABAH.jenis_nasabah', 'pegawai')
            ->where('tblNASABAH.status_nasabah', 'aktif')
            ->where('tblREKENING.status_rekening', 'aktif')
            ->select(
                'tblNASABAH.nasabah_id',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.kelas_unit',
                'tblNASABAH.gaji_pokok',
                'tblNASABAH.tunjangan',
                'tblNASABAH.no_hp'
            )
            ->orderBy('tblNASABAH.nama_nasabah', 'asc')
            ->get();

        // Cek apakah pegawai memiliki pinjaman aktif yang sedang berjalan
        $pinjamanAktifMap = DB::table('tblPINJAMAN')
            ->whereIn('status_pinjaman', ['diajukan', 'layak', 'cair'])
            ->get()
            ->keyBy('nasabah_id');

        foreach ($pegawaiList as $p) {
            $p->pinjaman_aktif = $pinjamanAktifMap->get($p->nasabah_id);
        }

        return view('bendahara_mini.pinjaman.ajukan', compact('pegawaiList'));
    }

    /**
     * Simpan formulir pengajuan pinjaman pegawai.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nasabah_id'       => 'required|exists:tblNASABAH,nasabah_id',
            'nominal_pinjaman' => 'required|numeric|min:100000',
            'tenor'            => 'required|integer|min:1|max:60',
            'metode_bayar'     => 'required|in:potong_gaji,tunai',
            'tgl_pengajuan'    => 'required|date',
        ], [
            'nasabah_id.required'       => 'Peminjam wajib dipilih.',
            'nominal_pinjaman.min'      => 'Nominal pinjaman minimal Rp 100.000.',
            'tenor.min'                 => 'Tenor pinjaman minimal 1 bulan.',
            'tenor.max'                 => 'Tenor pinjaman maksimal 60 bulan.',
            'metode_bayar.required'     => 'Metode pembayaran angsuran wajib dipilih.',
            'tgl_pengajuan.required'    => 'Tanggal pengajuan wajib diisi.',
        ]);

        // Validasi: hanya nasabah bertipe pegawai yang boleh meminjam
        $nasabah = DB::table('tblNASABAH')->where('nasabah_id', $request->nasabah_id)->first();
        if (!$nasabah || $nasabah->jenis_nasabah !== 'pegawai') {
            return back()->withInput()->with('error', 'Pinjaman hanya diperbolehkan bagi guru dan tenaga kependidikan (pegawai).');
        }

        // Validasi: nasabah wajib memiliki rekening yang telah diaktivasi fisik
        $rekening = DB::table('tblREKENING')->where('nasabah_id', $request->nasabah_id)->first();
        if (!$rekening || $rekening->status_rekening !== 'aktif') {
            return back()->withInput()->with('error', 'Pegawai yang dipilih belum mengaktifkan rekening di Bank Mini. Rekening harus berstatus aktif untuk mengajukan fasilitas pinjaman.');
        }

        // Validasi: tidak boleh memiliki pinjaman aktif yang sedang berjalan
        $pinjamanAktif = DB::table('tblPINJAMAN')
            ->where('nasabah_id', $request->nasabah_id)
            ->whereIn('status_pinjaman', ['diajukan', 'layak', 'cair'])
            ->first();

        if ($pinjamanAktif) {
            $statusLabel = strtoupper($pinjamanAktif->status_pinjaman);
            return back()->withInput()->with('error', "Nasabah {$nasabah->nama_nasabah} masih memiliki fasilitas pinjaman yang sedang berjalan (#PINJ-{$pinjamanAktif->pinjaman_id} - status: {$statusLabel}). Pengajuan baru hanya dapat diproses setelah seluruh angsuran pinjaman tersebut lunas.");
        }

        // Cek penguncian periode
        try {
            PeriodeLockService::assertPeriodeTerbuka($request->tgl_pengajuan);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Perbarui gaji pokok dan tunjangan jika diinput atau jika sebelumnya masih 0
        if ($request->filled('gaji_pokok') || $request->filled('tunjangan')) {
            $gajiBaru = $request->filled('gaji_pokok') ? (float) $request->input('gaji_pokok') : (float) $nasabah->gaji_pokok;
            $tunjBaru = $request->filled('tunjangan') ? (float) $request->input('tunjangan') : (float) $nasabah->tunjangan;
            DB::table('tblNASABAH')->where('nasabah_id', $request->nasabah_id)->update([
                'gaji_pokok' => $gajiBaru,
                'tunjangan'  => $tunjBaru,
            ]);
        }

        $pinjamanId = DB::table('tblPINJAMAN')->insertGetId([
            'nasabah_id'       => $request->nasabah_id,
            'user_id'          => auth()->id(),
            'nominal_pinjaman' => $request->nominal_pinjaman,
            'tenor'            => $request->tenor,
            'bunga_persen'     => 2.00,
            'tgl_pengajuan'    => $request->tgl_pengajuan,
            'metode_bayar'     => $request->metode_bayar,
            'status_pinjaman'  => 'diajukan',
        ], 'pinjaman_id');



        AuditLogService::log(
            aksi: 'PENGAJUAN_PINJAMAN',
            entitas: 'tblPINJAMAN',
            entitasId: $pinjamanId,
            dataBaru: [
                'nasabah_id'       => $request->nasabah_id,
                'nominal_pinjaman' => $request->nominal_pinjaman,
                'tenor'            => $request->tenor,
                'metode_bayar'     => $request->metode_bayar,
            ]
        );

        return redirect()->route('bbm.pinjaman.kelayakan')
            ->with('success', "Pengajuan pinjaman untuk {$nasabah->nama_nasabah} berhasil disimpan. Silakan lakukan penilaian kelayakan.");
    }

    /**
     * Halaman antrean dan formulir penilaian kelayakan kredit.
     */
    public function kelayakan()
    {
        // Pinjaman yang belum dinilai
        $pinjamanBelumDinilai = DB::table('tblPINJAMAN')
            ->join('tblNASABAH', 'tblPINJAMAN.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->leftJoin('tblKELAYAKAN', 'tblPINJAMAN.pinjaman_id', '=', 'tblKELAYAKAN.pinjaman_id')
            ->whereNull('tblKELAYAKAN.pinjaman_id')
            ->where('tblPINJAMAN.status_pinjaman', 'diajukan')
            ->select(
                'tblPINJAMAN.*',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.kelas_unit',
                'tblNASABAH.gaji_pokok',
                'tblNASABAH.tunjangan'
            )
            ->orderBy('tblPINJAMAN.tgl_pengajuan', 'asc')
            ->get();

        // 10 Riwayat penilaian kelayakan terakhir
        $pinjamanSudahDinilai = DB::table('tblPINJAMAN')
            ->join('tblNASABAH', 'tblPINJAMAN.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->join('tblKELAYAKAN', 'tblPINJAMAN.pinjaman_id', '=', 'tblKELAYAKAN.pinjaman_id')
            ->select(
                'tblPINJAMAN.*',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.kelas_unit',
                'tblKELAYAKAN.hasil_kelayakan',
                'tblKELAYAKAN.penghasilan',
                'tblKELAYAKAN.catatan',
                'tblKELAYAKAN.tgl_nilai'
            )
            ->orderByDesc('tblKELAYAKAN.tgl_nilai')
            ->limit(10)
            ->get();

        return view('bendahara_mini.pinjaman.kelayakan', compact('pinjamanBelumDinilai', 'pinjamanSudahDinilai'));
    }

    /**
     * Simpan hasil penilaian kelayakan (Layak/Tidak Layak).
     */
    public function simpanKelayakan(Request $request, $pinjamanId)
    {
        $request->validate([
            'hasil_kelayakan' => 'required|in:layak,tidak_layak',
            'penghasilan'     => 'required|numeric|min:0',
            'catatan'         => 'required|string|max:255',
        ], [
            'hasil_kelayakan.required' => 'Keputusan kelayakan wajib dipilih.',
            'penghasilan.required'     => 'Data penghasilan peminjam wajib diverifikasi.',
            'catatan.required'         => 'Catatan penilaian kelayakan wajib diisi sebagai dokumentasi.',
        ]);

        $pinjaman = DB::table('tblPINJAMAN')->where('pinjaman_id', $pinjamanId)->first();
        if (!$pinjaman) {
            return back()->with('error', 'Data pinjaman tidak ditemukan.');
        }

        if ($pinjaman->status_pinjaman !== 'diajukan') {
            return back()->with('error', 'Status pinjaman sudah bukan diajukan.');
        }

        $statusBaru = ($request->hasil_kelayakan === 'layak') ? 'layak' : 'ditolak';

        DB::transaction(function () use ($request, $pinjamanId, $pinjaman, $statusBaru) {
            DB::table('tblKELAYAKAN')->insert([
                'pinjaman_id'     => $pinjamanId,
                'user_id'         => auth()->id(),
                'penghasilan'     => $request->penghasilan,
                'hasil_kelayakan' => $request->hasil_kelayakan,
                'catatan'         => $request->catatan,
                'tgl_nilai'       => now()->toDateString(),
            ]);

            DB::table('tblPINJAMAN')
                ->where('pinjaman_id', $pinjamanId)
                ->update(['status_pinjaman' => $statusBaru]);

            // Jika di master nasabah gaji masih 0, perbarui dengan data penghasilan terverifikasi
            $nasabah = DB::table('tblNASABAH')->where('nasabah_id', $pinjaman->nasabah_id)->first();
            if ($nasabah && ((float)$nasabah->gaji_pokok + (float)$nasabah->tunjangan) == 0 && (float)$request->penghasilan > 0) {
                DB::table('tblNASABAH')->where('nasabah_id', $pinjaman->nasabah_id)->update([
                    'gaji_pokok' => $request->penghasilan,
                ]);
            }

            AuditLogService::log(
                aksi: 'ASSESSMENT_KELAYAKAN',
                entitas: 'tblKELAYAKAN',
                entitasId: $pinjamanId,
                dataLama: ['status' => $pinjaman->status_pinjaman],
                dataBaru: [
                    'hasil_kelayakan' => $request->hasil_kelayakan,
                    'status_pinjaman' => $statusBaru,
                    'penghasilan'     => $request->penghasilan,
                ]
            );
        });

        $pesan = ($statusBaru === 'layak')
            ? 'Penilaian kelayakan berhasil: Pinjaman berstatus LAYAK dan siap dicairkan.'
            : 'Penilaian kelayakan disimpan: Pinjaman DITOLAK dan alur pengajuan selesai.';

        return redirect()->route('bbm.pinjaman.kelayakan')->with('success', $pesan);
    }

    /**
     * Halaman daftar pinjaman yang layak dan siap dicairkan.
     */
    public function pencairan()
    {
        $siapCair = DB::table('tblPINJAMAN')
            ->join('tblNASABAH', 'tblPINJAMAN.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->join('tblKELAYAKAN', 'tblPINJAMAN.pinjaman_id', '=', 'tblKELAYAKAN.pinjaman_id')
            ->where('tblPINJAMAN.status_pinjaman', 'layak')
            ->select(
                'tblPINJAMAN.*',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.kelas_unit',
                'tblKELAYAKAN.catatan as catatan_kelayakan'
            )
            ->orderBy('tblPINJAMAN.tgl_pengajuan', 'asc')
            ->get();

        // Riwayat pencairan
        $riwayatCair = DB::table('tblPENCAIRAN')
            ->join('tblPINJAMAN', 'tblPENCAIRAN.pinjaman_id', '=', 'tblPINJAMAN.pinjaman_id')
            ->join('tblNASABAH', 'tblPINJAMAN.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->select(
                'tblPENCAIRAN.*',
                'tblPINJAMAN.nominal_pinjaman',
                'tblPINJAMAN.tenor',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.kelas_unit'
            )
            ->orderByDesc('tblPENCAIRAN.pencairan_id')
            ->limit(10)
            ->get();

        $kasTunai = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();
        $kasBank  = DB::table('tblKAS')->where('jenis_kas', 'bank')->first();

        return view('bendahara_mini.pinjaman.cair', compact('siapCair', 'riwayatCair', 'kasTunai', 'kasBank'));
    }

    /**
     * Eksekusi pencairan pinjaman secara atomik (DB::transaction).
     */
    public function prosesPencairan(Request $request, $pinjamanId)
    {
        $request->validate([
            'tgl_cair'         => 'required|date',
            'metode_pencairan' => 'nullable|in:cash,transfer',
            'catatan'          => 'nullable|string|max:255',
        ], [
            'tgl_cair.required' => 'Tanggal pencairan dana wajib ditentukan.',
        ]);

        $metodeCair = $request->input('metode_pencairan', 'cash');

        $pinjaman = DB::table('tblPINJAMAN')->where('pinjaman_id', $pinjamanId)->first();
        if (!$pinjaman) {
            return back()->with('error', 'Pinjaman tidak ditemukan.');
        }

        if ($pinjaman->status_pinjaman !== 'layak') {
            return back()->with('error', 'Hanya pinjaman yang sudah berstatus Layak yang dapat dicairkan.');
        }

        $nasabah = DB::table('tblNASABAH')->where('nasabah_id', $pinjaman->nasabah_id)->first();
        if (!$nasabah) {
            return back()->with('error', 'Data nasabah peminjam tidak ditemukan.');
        }

        // Cek penguncian periode
        try {
            $periode = PeriodeLockService::assertPeriodeTerbuka($request->tgl_cair);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Tentukan tempat kas sumber pencairan (Tunai Fisik vs Rekening Bank)
        $jenisKasTarget = ($metodeCair === 'transfer') ? 'bank' : 'kas';
        $kasSumber = DB::table('tblKAS')->where('jenis_kas', $jenisKasTarget)->first();

        if (!$kasSumber) {
            $labelKas = ($metodeCair === 'transfer') ? 'Kas Bank Bendahara' : 'Kas Tunai Bank Mini';
            return back()->with('error', "Master {$labelKas} tidak ditemukan dalam sistem.");
        }

        if ($kasSumber->saldo < $pinjaman->nominal_pinjaman) {
            $labelKas = ($metodeCair === 'transfer') ? 'Kas Bank Bendahara (BJB)' : 'Kas Tunai (Brankas)';
            return back()->with('error', "Saldo {$labelKas} saat ini (Rp " . number_format($kasSumber->saldo, 0, ',', '.') . ") tidak mencukupi untuk mencairkan pinjaman sebesar Rp " . number_format($pinjaman->nominal_pinjaman, 0, ',', '.') . ".");
        }

        DB::transaction(function () use ($pinjaman, $nasabah, $request, $periode, $kasSumber, $metodeCair) {
            // 1. Simpan baris pencairan
            $pencairanId = DB::table('tblPENCAIRAN')->insertGetId([
                'pinjaman_id'  => $pinjaman->pinjaman_id,
                'user_id'      => auth()->id(),
                'tgl_cair'     => $request->tgl_cair,
                'nominal_cair' => $pinjaman->nominal_pinjaman,
                'metode_cair'  => $metodeCair,
                'catatan_cair' => $request->catatan,
            ], 'pencairan_id');

            // 2. Bentuk baris jadwal angsuran (jasa flat 2% per bulan)
            $jadwalList = PinjamanCalculatorService::generateJadwalAngsuran(
                pinjamanId: $pinjaman->pinjaman_id,
                pokokAwal: $pinjaman->nominal_pinjaman,
                tenor: $pinjaman->tenor,
                tglMulai: $request->tgl_cair,
                persenBunga: $pinjaman->bunga_persen
            );

            foreach ($jadwalList as $baris) {
                DB::table('tblJADWAL_ANGSURAN')->insert($baris);
            }

            // 3. Catat mutasi kas keluar dengan uraian deskriptif mencantumkan nama peminjam
            $labelMetode = ($metodeCair === 'transfer') ? 'Transfer (Bank)' : 'Tunai (Cash)';
            $uraian = "Pencairan pinjaman {$labelMetode} #PINJ-{$pinjaman->pinjaman_id} a.n. {$nasabah->nama_nasabah} ({$pinjaman->tenor} bln)";

            DB::table('tblMUTASI_KAS')->insert([
                'kas_id'       => $kasSumber->kas_id,
                'periode_id'   => $periode->periode_id,
                'user_id'      => auth()->id(),
                'transaksi_id' => null,
                'angsuran_id'  => null,
                'pencairan_id' => $pencairanId,
                'tgl_mutasi'   => $request->tgl_cair,
                'arah'         => 'keluar',
                'nominal'      => $pinjaman->nominal_pinjaman,
                'uraian'       => $uraian,
            ]);

            // 4. Update saldo kas sumber (Tunai atau Bank)
            DB::table('tblKAS')
                ->where('kas_id', $kasSumber->kas_id)
                ->decrement('saldo', $pinjaman->nominal_pinjaman);

            // 5. Update status pinjaman menjadi cair
            DB::table('tblPINJAMAN')
                ->where('pinjaman_id', $pinjaman->pinjaman_id)
                ->update(['status_pinjaman' => 'cair']);

            AuditLogService::log(
                aksi: 'PENCAIRAN_PINJAMAN',
                entitas: 'tblPENCAIRAN',
                entitasId: $pencairanId,
                dataBaru: [
                    'pinjaman_id'  => $pinjaman->pinjaman_id,
                    'nominal_cair' => $pinjaman->nominal_pinjaman,
                    'tenor'        => $pinjaman->tenor,
                    'tgl_cair'     => $request->tgl_cair,
                    'metode_cair'  => $metodeCair,
                ]
            );
        });

        $infoMetode = ($metodeCair === 'transfer') ? 'melalui Transfer Rekening Bank' : 'secara Tunai di Loket';
        return redirect()->route('bbm.pinjaman.cair')
            ->with('success', "Pinjaman #PINJ-{$pinjaman->pinjaman_id} a.n. {$nasabah->nama_nasabah} berhasil dicairkan {$infoMetode}! Jadwal angsuran telah dibentuk otomatis.");
    }
}

