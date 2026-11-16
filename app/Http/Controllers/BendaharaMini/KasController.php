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
     * Resolve filter periode transaksi (harian, bulanan, tahunan, atau semua).
     */
    private function resolveFilter(Request $request, string $defaultType = 'harian'): array
    {
        $filterType = $request->query('filter_type', $defaultType);
        if (!in_array($filterType, ['harian', 'bulanan', 'tahunan', 'semua'])) {
            $filterType = $defaultType;
        }

        $tanggal = $request->query('tanggal', now()->toDateString());
        $bulan   = (int) $request->query('bulan', now()->format('m'));
        $tahun   = (int) $request->query('tahun', now()->format('Y'));

        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        switch ($filterType) {
            case 'harian':
                $label = \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y');
                break;
            case 'bulanan':
                $namaBulan = $bulanNames[$bulan] ?? ('Bulan ' . $bulan);
                $label = "{$namaBulan} {$tahun}";
                break;
            case 'tahunan':
                $label = "Tahun {$tahun}";
                break;
            default:
                $label = 'Seluruh Riwayat';
                break;
        }

        return [
            'type'    => $filterType,
            'tanggal' => $tanggal,
            'bulan'   => $bulan,
            'tahun'   => $tahun,
            'label'   => $label,
        ];
    }

    /**
     * Terapkan filter tanggal ke query builder.
     */
    private function applyFilter($query, array $filter, string $column = 'tgl_mutasi')
    {
        if ($filter['type'] === 'harian') {
            $query->whereDate($column, $filter['tanggal']);
        } elseif ($filter['type'] === 'bulanan') {
            $query->whereYear($column, $filter['tahun'])
                  ->whereMonth($column, $filter['bulan']);
        } elseif ($filter['type'] === 'tahunan') {
            $query->whereYear($column, $filter['tahun']);
        }
        return $query;
    }

    /**
     * Tampilkan buku Kas Harian (Kas Tunai Fisik di Loket/Brankas Bank Mini).
     */
    public function harian(Request $request)
    {
        $kasFisik = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();
        if (!$kasFisik) {
            $kasId = DB::table('tblKAS')->insertGetId([
                'nama_kas'  => 'Kas Tunai Bank Mini',
                'jenis_kas' => 'kas',
                'saldo'     => 0,
            ], 'kas_id');
            $kasFisik = DB::table('tblKAS')->where('kas_id', $kasId)->first();
        }

        $filter = $this->resolveFilter($request, 'harian');

        // Mutasi kas harian terfilter
        $mutasiQuery = DB::table('tblMUTASI_KAS as mk')
            ->join('tblUSER as u', 'mk.user_id', '=', 'u.user_id')
            ->where('mk.kas_id', $kasFisik->kas_id);

        $this->applyFilter($mutasiQuery, $filter, 'mk.tgl_mutasi');

        $mutasiList = $mutasiQuery
            ->select('mk.*', 'u.nama_user')
            ->orderByDesc('mk.mutasi_kas_id')
            ->paginate(10)
            ->appends($request->query());

        // Ringkasan metrik sesuai filter periode
        $statQuery = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasFisik->kas_id);
        $this->applyFilter($statQuery, $filter, 'tgl_mutasi');

        $masukHariIni = (clone $statQuery)->where('arah', 'masuk')->sum('nominal');
        $keluarHariIni = (clone $statQuery)->where('arah', 'keluar')->sum('nominal');
        $totalMutasiHariIni = (clone $statQuery)->count();

        $availableYears = range((int)date('Y'), (int)date('Y') - 4);

        return view('bendahara_mini.kas_harian', compact(
            'kasFisik', 'mutasiList', 'masukHariIni', 'keluarHariIni', 'totalMutasiHariIni', 'filter', 'availableYears'
        ));
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
            ], 'mutasi_kas_id');

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
    public function bank(Request $request)
    {
        $kasBank = DB::table('tblKAS')->where('jenis_kas', 'bank')->first();
        if (!$kasBank) {
            $kasId = DB::table('tblKAS')->insertGetId([
                'nama_kas'  => 'Bank Jabar Banten (BJB)',
                'jenis_kas' => 'bank',
                'saldo'     => 0,
            ], 'kas_id');
            $kasBank = DB::table('tblKAS')->where('kas_id', $kasId)->first();
        }

        $filter = $this->resolveFilter($request, 'harian');

        // Mutasi rekening bank terfilter
        $mutasiQuery = DB::table('tblMUTASI_KAS as mk')
            ->join('tblUSER as u', 'mk.user_id', '=', 'u.user_id')
            ->where('mk.kas_id', $kasBank->kas_id);

        $this->applyFilter($mutasiQuery, $filter, 'mk.tgl_mutasi');

        $mutasiList = $mutasiQuery
            ->select('mk.*', 'u.nama_user')
            ->orderByDesc('mk.mutasi_kas_id')
            ->paginate(10)
            ->appends($request->query());

        // Ringkasan metrik rekening bank sesuai filter periode
        $statQuery = DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasBank->kas_id);
        $this->applyFilter($statQuery, $filter, 'tgl_mutasi');

        $masukBank = (clone $statQuery)->where('arah', 'masuk')->sum('nominal');
        $keluarBank = (clone $statQuery)->where('arah', 'keluar')->sum('nominal');
        $totalTransaksiBank = (clone $statQuery)->count();

        $availableYears = range((int)date('Y'), (int)date('Y') - 4);

        return view('bendahara_mini.kas_bank', compact(
            'kasBank', 'mutasiList', 'masukBank', 'keluarBank', 'totalTransaksiBank', 'filter', 'availableYears'
        ));
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

