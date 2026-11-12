<?php

namespace App\Http\Controllers\BendaharaMini;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\PeriodeLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KasController extends Controller
{
    /**
     * Tampilkan buku Kas Harian (Kas Tunai Fisik di Loket/Brankas Bank Mini).
     */
    public function harian()
    {
        $kasFisik = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();
        if (!$kasFisik) {
            $kasId = DB::table('tblKAS')->insertGetId([
                'nama_kas'  => 'Kas Tunai Bank Mini',
                'jenis_kas' => 'kas',
                'saldo'     => 0,
            ]);
            $kasFisik = DB::table('tblKAS')->where('kas_id', $kasId)->first();
        }

        // Mutasi kas harian (dipaginasi 10 per halaman)
        $mutasiList = DB::table('tblMUTASI_KAS as mk')
            ->join('tblUSER as u', 'mk.user_id', '=', 'u.user_id')
            ->where('mk.kas_id', $kasFisik->kas_id)
            ->select('mk.*', 'u.nama_user')
            ->orderByDesc('mk.mutasi_kas_id')
            ->paginate(10);

        // Ringkasan hari ini
        $hariIni = now()->toDateString();
        $masukHariIni = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasFisik->kas_id)
            ->where('tgl_mutasi', $hariIni)
            ->where('arah', 'masuk')
            ->sum('nominal');

        $keluarHariIni = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasFisik->kas_id)
            ->where('tgl_mutasi', $hariIni)
            ->where('arah', 'keluar')
            ->sum('nominal');

        $totalMutasiHariIni = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasFisik->kas_id)
            ->where('tgl_mutasi', $hariIni)
            ->count();

        return view('bendahara_mini.kas_harian', compact('kasFisik', 'mutasiList', 'masukHariIni', 'keluarHariIni', 'totalMutasiHariIni'));
    }

    /**
     * Simpan transaksi mutasi kas harian manual.
     */
    public function simpanHarian(Request $request)
    {
        $request->validate([
            'tgl_mutasi' => 'required|date',
            'arah'       => 'required|in:masuk,keluar',
            'nominal'    => 'required|numeric|min:1000',
            'uraian'     => 'required|string|max:255',
        ], [
            'nominal.min'     => 'Nominal mutasi kas minimal Rp 1.000.',
            'uraian.required' => 'Keterangan/uraian mutasi wajib diisi.',
        ]);

        try {
            $periode = PeriodeLockService::assertPeriodeTerbuka($request->tgl_mutasi);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $kasFisik = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();

        // Validasi kecukupan saldo jika keluar
        if ($request->arah === 'keluar' && $kasFisik->saldo < $request->nominal) {
            return back()->withInput()->with('error', 'Saldo kas tunai tidak mencukupi untuk transaksi pengeluaran ini.');
        }

        DB::transaction(function () use ($kasFisik, $periode, $request) {
            $mutasiId = DB::table('tblMUTASI_KAS')->insertGetId([
                'kas_id'       => $kasFisik->kas_id,
                'periode_id'   => $periode->periode_id,
                'user_id'      => auth()->id(),
                'transaksi_id' => null,
                'angsuran_id'  => null,
                'pencairan_id' => null,
                'tgl_mutasi'   => $request->tgl_mutasi,
                'arah'         => $request->arah,
                'nominal'      => $request->nominal,
                'uraian'       => $request->uraian,
            ]);

            if ($request->arah === 'masuk') {
                DB::table('tblKAS')->where('kas_id', $kasFisik->kas_id)->increment('saldo', $request->nominal);
            } else {
                DB::table('tblKAS')->where('kas_id', $kasFisik->kas_id)->decrement('saldo', $request->nominal);
            }

            AuditLogService::log(
                aksi: 'MUTASI_KAS_HARIAN',
                entitas: 'tblMUTASI_KAS',
                entitasId: $mutasiId,
                dataBaru: [
                    'kas_id'  => $kasFisik->kas_id,
                    'arah'    => $request->arah,
                    'nominal' => $request->nominal,
                    'uraian'  => $request->uraian,
                ]
            );
        });

        return redirect()->route('kas.harian')->with('success', 'Transaksi kas harian berhasil dicatat.');
    }

    /**
     * Tampilkan buku Kas Bank (Rekening Koran Perbankan).
     */
    public function bank()
    {
        $kasBank = DB::table('tblKAS')->where('jenis_kas', 'bank')->first();
        if (!$kasBank) {
            $kasId = DB::table('tblKAS')->insertGetId([
                'nama_kas'  => 'Bank Jabar Banten (BJB)',
                'jenis_kas' => 'bank',
                'saldo'     => 0,
            ]);
            $kasBank = DB::table('tblKAS')->where('kas_id', $kasId)->first();
        }

        $mutasiList = DB::table('tblMUTASI_KAS as mk')
            ->join('tblUSER as u', 'mk.user_id', '=', 'u.user_id')
            ->where('mk.kas_id', $kasBank->kas_id)
            ->select('mk.*', 'u.nama_user')
            ->orderByDesc('mk.mutasi_kas_id')
            ->paginate(10);

        // Ringkasan transaksi rekening bank
        $masukBank = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasBank->kas_id)
            ->where('arah', 'masuk')
            ->sum('nominal');

        $keluarBank = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasBank->kas_id)
            ->where('arah', 'keluar')
            ->sum('nominal');

        $totalTransaksiBank = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasBank->kas_id)
            ->count();

        return view('bendahara_mini.kas_bank', compact('kasBank', 'mutasiList', 'masukBank', 'keluarBank', 'totalTransaksiBank'));
    }

    /**
     * Simpan mutasi kas bank manual.
     */
    public function simpanBank(Request $request)
    {
        $request->validate([
            'tgl_mutasi' => 'required|date',
            'arah'       => 'required|in:masuk,keluar',
            'nominal'    => 'required|numeric|min:1000',
            'uraian'     => 'required|string|max:255',
        ]);

        try {
            $periode = PeriodeLockService::assertPeriodeTerbuka($request->tgl_mutasi);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $kasBank = DB::table('tblKAS')->where('jenis_kas', 'bank')->first();

        if ($request->arah === 'keluar' && $kasBank->saldo < $request->nominal) {
            return back()->withInput()->with('error', 'Saldo kas bank tidak mencukupi untuk transaksi penarikan/transfer ini.');
        }

        DB::transaction(function () use ($kasBank, $periode, $request) {
            $mutasiId = DB::table('tblMUTASI_KAS')->insertGetId([
                'kas_id'       => $kasBank->kas_id,
                'periode_id'   => $periode->periode_id,
                'user_id'      => auth()->id(),
                'transaksi_id' => null,
                'angsuran_id'  => null,
                'pencairan_id' => null,
                'tgl_mutasi'   => $request->tgl_mutasi,
                'arah'         => $request->arah,
                'nominal'      => $request->nominal,
                'uraian'       => $request->uraian,
            ], 'mutasi_kas_id');

            if ($request->arah === 'masuk') {
                DB::table('tblKAS')->where('kas_id', $kasBank->kas_id)->increment('saldo', $request->nominal);
            } else {
                DB::table('tblKAS')->where('kas_id', $kasBank->kas_id)->decrement('saldo', $request->nominal);
            }

            AuditLogService::log(
                aksi: 'MUTASI_KAS_BANK',
                entitas: 'tblMUTASI_KAS',
                entitasId: $mutasiId,
                dataBaru: [
                    'kas_id'  => $kasBank->kas_id,
                    'arah'    => $request->arah,
                    'nominal' => $request->nominal,
                    'uraian'  => $request->uraian,
                ]
            );
        });

        return redirect()->route('kas.bank')->with('success', 'Transaksi kas bank berhasil dicatat.');
    }
}

