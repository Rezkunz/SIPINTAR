<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SiswaPraktik\BukaRekeningController;
use App\Http\Requests\Teller\StoreTellerTransactionRequest;
use App\Models\Rekening;
use App\Services\TellerTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TellerTransactionController extends Controller
{
    public function bukaRekening(Request $request)
    {
        if ($request->user()->hasRole('teller')) {
            return app(TellerDashboardController::class)->bukaRekening($request);
        }

        return app(BukaRekeningController::class)->create();
    }

    public function setoran(Request $request): View|RedirectResponse
    {
        if (!$request->user()->hasRole('teller')) {
            return redirect()->route('tabungan.draft', ['jenis' => 'setoran']);
        }

        return view('teller.setoran', ['rekeningList' => $this->activeAccounts()]);
    }

    public function penarikan(Request $request): View|RedirectResponse
    {
        if (!$request->user()->hasRole('teller')) {
            return redirect()->route('tabungan.draft', ['jenis' => 'penarikan']);
        }

        return view('teller.penarikan', ['rekeningList' => $this->activeAccounts()]);
    }

    public function storeSetoran(StoreTellerTransactionRequest $request, TellerTransactionService $service): RedirectResponse
    {
        $result = $service->storeDirect((int) auth()->id(), $request->validated('no_rek'), (float) $request->validated('nominal'), 'setoran');

        return redirect()->route('tabungan.bukti.show', $result['transaksi_id'])->with('success', 'Setoran berhasil dicatat.');
    }

    public function storePenarikan(StoreTellerTransactionRequest $request, TellerTransactionService $service): RedirectResponse
    {
        $result = $service->storeDirect((int) auth()->id(), $request->validated('no_rek'), (float) $request->validated('nominal'), 'penarikan');

        return redirect()->route('tabungan.bukti.show', $result['transaksi_id'])->with('success', 'Penarikan berhasil dicatat.');
    }

    public function searchRekening(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $results = Rekening::with('nasabah')
            ->where('status_rekening', 'aktif')
            ->where(function ($builder) use ($query, $likeOp) {
                $builder->where('no_rek', $likeOp, "%{$query}%")
                    ->orWhereHas('nasabah', fn ($nasabah) => $nasabah->where('nama_nasabah', $likeOp, "%{$query}%"));
            })
            ->limit(10)
            ->get()
            ->map(fn ($rekening) => [
                'no_rek' => $rekening->no_rek,
                'nama_nasabah' => $rekening->nasabah?->nama_nasabah,
                'saldo' => (float) $rekening->saldo,
            ]);

        return response()->json($results);
    }

    public function saldo(Request $request): View
    {
        $rekening = $this->findAccount($request->query('no_rek'));

        return view('teller.saldo', compact('rekening'));
    }

    public function mutasi(Request $request): View
    {
        $rekening = $this->findAccount($request->query('no_rek'), true);
        $mutasi = collect();
        $mode = $request->query('mode') === 'koreksi' && $rekening?->status_rekening !== 'tutup' ? 'koreksi' : 'lihat';
        $types = \Illuminate\Support\Facades\DB::table('tblJENIS_TRANSAKSI')
            ->whereIn('nama_jenis', ['setoran', 'penarikan', 'jasa_tabungan'])
            ->orderBy('jenis_transaksi_id')
            ->get();

        $sudahDikoreksi = [];
        if ($rekening) {
            $mutasi = \Illuminate\Support\Facades\DB::table('tblTRANSAKSI')
                ->join('tblJENIS_TRANSAKSI', 'tblTRANSAKSI.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
                ->leftJoin('tblPERIODE', 'tblTRANSAKSI.periode_id', '=', 'tblPERIODE.periode_id')
                ->where('tblTRANSAKSI.rekening_id', $rekening->rekening_id)
                ->select(
                    'tblTRANSAKSI.*',
                    'tblJENIS_TRANSAKSI.nama_jenis',
                    'tblPERIODE.status_periode',
                    'tblPERIODE.bulan as periode_bulan',
                    'tblPERIODE.tahun as periode_tahun'
                )
                ->orderByDesc('tblTRANSAKSI.tgl_transaksi')
                ->orderByDesc('tblTRANSAKSI.transaksi_id')
                ->paginate(15)
                ->withQueryString();

            // Transaksi yang sudah dikoreksi, beserta transaksi pembalik & hasil koreksinya
            \Illuminate\Support\Facades\DB::table('tblAUDIT_LOG')
                ->where('aksi', 'KOREKSI_TRANSAKSI')
                ->where('entitas', 'tblTRANSAKSI')
                ->get(['entitas_id', 'data_baru'])
                ->each(function ($log) use (&$sudahDikoreksi) {
                    $data = json_decode((string) $log->data_baru, true) ?: [];
                    $sudahDikoreksi[(int) $log->entitas_id] = 'dikoreksi';
                    if (!empty($data['reversal_id'])) {
                        $sudahDikoreksi[(int) $data['reversal_id']] = 'pembalik';
                    }
                    if (!empty($data['corrected_id'])) {
                        $sudahDikoreksi[(int) $data['corrected_id']] = 'hasil';
                    }
                });
        }

        return view('teller.mutasi', compact('rekening', 'mutasi', 'mode', 'types', 'sudahDikoreksi'));
    }

    public function logHarian(Request $request): View
    {
        $tanggal = $request->query('tanggal', now()->toDateString());
        $transaksi = $this->dailyLogQuery($tanggal)->paginate(30)->withQueryString();
        $ringkasan = $this->dailySummary($tanggal);

        return view('teller.log_transaksi', compact('tanggal', 'transaksi', 'ringkasan'));
    }

    public function printLogHarian(Request $request): View
    {
        $tanggal = $request->query('tanggal', now()->toDateString());
        $transaksi = $this->dailyLogQuery($tanggal)->get();
        $ringkasan = $this->dailySummary($tanggal);

        return view('teller.log_transaksi_cetak', compact('tanggal', 'transaksi', 'ringkasan'));
    }

    public function printMutasi(Request $request): View
    {
        $rekening = $this->findAccount($request->query('no_rek'), true);
        abort_unless($rekening, 404);

        $mutasi = \Illuminate\Support\Facades\DB::table('tblTRANSAKSI')
            ->join('tblJENIS_TRANSAKSI', 'tblTRANSAKSI.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
            ->where('tblTRANSAKSI.rekening_id', $rekening->rekening_id)
            ->select('tblTRANSAKSI.*', 'tblJENIS_TRANSAKSI.nama_jenis')
            ->orderBy('tblTRANSAKSI.tgl_transaksi')
            ->get();

        return view('teller.mutasi_cetak', compact('rekening', 'mutasi'));
    }

    public function bukti(): View
    {
        $buktiList = \Illuminate\Support\Facades\DB::table('tblBUKTI as b')
            ->join('tblTRANSAKSI as t', 'b.transaksi_id', '=', 't.transaksi_id')
            ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->orderByDesc('b.bukti_id')
            ->paginate(15);

        return view('teller.bukti', compact('buktiList'));
    }

    public function showBukti(int $transaksiId): View
    {
        $bukti = \Illuminate\Support\Facades\DB::table('tblBUKTI as b')
            ->join('tblTRANSAKSI as t', 'b.transaksi_id', '=', 't.transaksi_id')
            ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
            ->where('t.transaksi_id', $transaksiId)
            ->select('b.*', 't.*', 'r.no_rek', 'n.nama_nasabah', 'jt.nama_jenis')
            ->first();

        abort_unless($bukti, 404);

        return view('teller.bukti_show', compact('bukti'));
    }

    private function activeAccounts()
    {
        return Rekening::with('nasabah')
            ->where('status_rekening', 'aktif')
            ->orderBy('no_rek')
            ->limit(100)
            ->get();
    }

    private function findAccount(?string $noRek, bool $termasukTertutup = false)
    {
        if (!$noRek) {
            return null;
        }

        return Rekening::with('nasabah')
            ->where('no_rek', $noRek)
            ->whereIn('status_rekening', $termasukTertutup ? ['aktif', 'tutup'] : ['aktif'])
            ->first();
    }

    private function dailyLogQuery(string $tanggal)
    {
        return \Illuminate\Support\Facades\DB::table('tblTRANSAKSI as t')
            ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
            ->leftJoin('tblUSER as u', 't.user_id', '=', 'u.user_id')
            ->leftJoin('tblAUDIT_LOG as al', function ($join) {
                $join->on('al.entitas_id', '=', 't.transaksi_id')
                    ->where('al.entitas', '=', 'tblTRANSAKSI')
                    ->where('al.aksi', '=', 'KOREKSI_TRANSAKSI');
            })
            ->whereDate('t.tgl_transaksi', $tanggal)
            ->select(
                't.*',
                'r.no_rek',
                'n.nama_nasabah',
                'jt.nama_jenis',
                'u.nama_user as nama_pencatat',
                'al.audit_log_id as koreksi_id'
            )
            ->orderBy('t.tgl_transaksi');
    }

    private function dailySummary(string $tanggal): array
    {
        $summary = \Illuminate\Support\Facades\DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', $tanggal)
            ->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        return [
            'total_transaksi' => (int) $summary->total_transaksi,
            'total_debit' => (float) $summary->total_debit,
            'total_kredit' => (float) $summary->total_kredit,
            'total_bunga' => (float) \Illuminate\Support\Facades\DB::table('tblTRANSAKSI as t')
                ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
                ->whereDate('t.tgl_transaksi', $tanggal)
                ->where('jt.nama_jenis', 'jasa_tabungan')
                ->sum('t.kredit'),
            'total_koreksi' => (int) \Illuminate\Support\Facades\DB::table('tblAUDIT_LOG')
                ->where('aksi', 'KOREKSI_TRANSAKSI')
                ->where('entitas', 'tblTRANSAKSI')
                ->whereDate('created_at', $tanggal)
                ->count(),
        ];
    }
}