<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TellerDashboardController extends Controller
{
    /**
     * Tampilkan dashboard loket operasional kasir teller dengan metrik transaksi nyata.
     */
    public function index()
    {
        $today = now()->toDateString();

        // 1. Hitung total draft pending dari siswa praktik
        $draftPendingCount = DB::table('tblDRAFT')
            ->where('status_verifikasi', 'pending')
            ->count();

        // 2. Transaksi Hari Ini
        $totalSetoranHariIni = DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', $today)
            ->sum('kredit');

        $totalPenarikanHariIni = DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', $today)
            ->sum('debit');

        $transaksiHariIniCount = DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', $today)
            ->count();

        // 3. Draft Menunggu Verifikasi (limit 5)
        $drafts = DB::table('tblDRAFT')
            ->join('tblREKENING', 'tblDRAFT.rekening_id', '=', 'tblREKENING.rekening_id')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->leftJoin('tblUSER as siswa', 'tblDRAFT.siswa_id', '=', 'siswa.user_id')
            ->join('tblJENIS_TRANSAKSI', 'tblDRAFT.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
            ->select(
                'tblDRAFT.*',
                'tblNASABAH.nama_nasabah',
                'tblREKENING.no_rek',
                'siswa.nama_user as nama_siswa',
                'tblJENIS_TRANSAKSI.nama_jenis as nama_jenis_transaksi'
            )
            ->where('tblDRAFT.status_verifikasi', 'pending')
            ->orderBy('tblDRAFT.tgl_input', 'asc')
            ->limit(5)
            ->get();

        // 4. Saldo Kas Brankas / Kas Harian
        $kasHariIni = DB::table('tblKAS')
            ->where('jenis_kas', 'kas')
            ->first();

        return view('teller.dashboard', compact(
            'draftPendingCount',
            'totalSetoranHariIni',
            'totalPenarikanHariIni',
            'transaksiHariIniCount',
            'drafts',
            'kasHariIni'
        ));
    }

    /**
     * Tampilkan rekening yang sudah diaktifkan oleh Siswa Praktik.
     */
    public function bukaRekening(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = DB::table('tblREKENING')
            ->join('tblNASABAH', 'tblREKENING.nasabah_id', '=', 'tblNASABAH.nasabah_id')
            ->leftJoin('tblUSER', 'tblNASABAH.nasabah_id', '=', 'tblUSER.nasabah_id')
            ->select(
                'tblREKENING.*',
                'tblNASABAH.nama_nasabah',
                'tblNASABAH.jenis_nasabah',
                'tblNASABAH.kelas_unit',
                'tblNASABAH.no_hp as nasabah_no_hp',
                'tblUSER.no_induk',
                'tblUSER.email'
            )
            ->where('tblREKENING.status_rekening', 'aktif')
            ->orderBy('tblREKENING.rekening_id', 'desc');

        if ($search) {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('tblNASABAH.nama_nasabah', $likeOp, "%{$search}%")
                  ->orWhere('tblUSER.no_induk', $likeOp, "%{$search}%")
                  ->orWhere('tblREKENING.no_rek', $likeOp, "%{$search}%");
            });
        }

        $activeRekening = $query->paginate(10)->withQueryString();
        $totalAktif = DB::table('tblREKENING')->where('status_rekening', 'aktif')->count();

        return view('teller.rekening_aktif', compact('activeRekening', 'totalAktif', 'search'));
        return view('teller.buka_rekening', compact('pendingRekening', 'totalPending', 'totalAktif', 'search'));
    }

    /**
     * Otorisasi dan aktivasi rekening baru dengan setoran awal fisik (Use Case: Membuka Rekening).
     */
    public function aktivasiRekening(Request $request, $rekeningId)
    {
        $request->validate([
            'setoran_awal' => ['required', 'numeric', 'min:10000'],
        ], [
            'setoran_awal.required' => 'Nominal uang setoran awal tunai wajib diisi.',
            'setoran_awal.numeric'  => 'Setoran awal harus berupa angka.',
            'setoran_awal.min'      => 'Setoran awal minimum pembukaan rekening adalah Rp 10.000.',
        ]);

        $setoranAwal = (float) $request->input('setoran_awal');

        try {
            DB::transaction(function () use ($rekeningId, $setoranAwal) {
                $rekening = DB::table('tblREKENING')->where('rekening_id', $rekeningId)->first();
                if (!$rekening) {
                    throw new \Exception('Rekening tidak ditemukan.');
                }

                // Pastikan periode bulan ini terbuka (ditolak bila sudah ditutup Manager)
                $periodeId = \App\Services\PeriodeLockService::assertPeriodeTerbuka(now()->toDateString())->periode_id;

                // 1. Update status rekening menjadi aktif & isi saldo awal
                DB::table('tblREKENING')->where('rekening_id', $rekeningId)->update([
                    'status_rekening' => 'aktif',
                    'saldo'           => $setoranAwal,
                    'tgl_buka'        => now()->toDateString(),
                ]);

                // 2. Catat transaksi setoran awal di tblTRANSAKSI (jenis_transaksi_id = 1 = setoran)
                $transaksiId = DB::table('tblTRANSAKSI')->insertGetId([
                    'rekening_id'        => $rekeningId,
                    'jenis_transaksi_id' => 1,
                    'periode_id'         => $periodeId,
                    'draft_id'           => null,
                    'user_id'            => auth()->id(),
                    'debit'              => 0.00,
                    'kredit'             => $setoranAwal,
                    'saldo_akhir'        => $setoranAwal,
                    'tgl_transaksi'      => now(),
                ], 'transaksi_id');

                // 3. Masukkan uang fisik ke kas tunai brankas dan catat mutasinya, ditautkan ke
                //    transaksi tabungan agar saldo kas per tanggal (Neraca, Tutup Periode) tepat dan
                //    laporan pinjaman dapat memisahkannya
                $kasTunai = DB::table('tblKAS')->where('jenis_kas', 'kas')->orderBy('kas_id')->first();
                if (!$kasTunai) {
                    throw new \RuntimeException('Kas tunai Bank Mini belum tersedia. Hubungi Bendahara Bank Mini.');
                }

                $nasabah = DB::table('tblNASABAH')->where('nasabah_id', $rekening->nasabah_id)->value('nama_nasabah');
                $mutasiId = DB::table('tblMUTASI_KAS')->insertGetId([
                    'kas_id'       => $kasTunai->kas_id,
                    'periode_id'   => $periodeId,
                    'user_id'      => auth()->id(),
                    'transaksi_id' => $transaksiId,
                    'tgl_mutasi'   => now()->toDateString(),
                    'arah'         => 'masuk',
                    'nominal'      => $setoranAwal,
                    'uraian'       => "Setoran awal tabungan {$rekening->no_rek}" . ($nasabah ? " a.n. {$nasabah}" : ''),
                ], 'mutasi_kas_id');

                DB::table('tblKAS')->where('kas_id', $kasTunai->kas_id)->increment('saldo', $setoranAwal);

                \App\Services\AuditLogService::log(
                    aksi: 'INSERT',
                    entitas: 'tblTRANSAKSI',
                    entitasId: $transaksiId,
                    dataBaru: ['rekening_id' => (int) $rekeningId, 'jenis' => 'setoran_awal', 'kredit' => $setoranAwal, 'mutasi_kas_id' => $mutasiId]
                );
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Rekening nasabah berhasil diverifikasi dan diaktifkan dengan setoran awal Rp ' . number_format($setoranAwal, 0, ',', '.') . '. Buku tabungan dan slip setoran siap dicetak!');
    }
}
