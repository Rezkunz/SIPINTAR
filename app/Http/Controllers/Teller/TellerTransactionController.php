<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Concerns\TanganiKesalahanInput;
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
use Throwable;

class TellerTransactionController extends Controller
{
    use TanganiKesalahanInput;

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
        try {
            $enforceMin = !$request->boolean('abaikan_minimal');
            $result = $service->storeDirect(
                (int) auth()->id(),
                $request->validated('no_rek'),
                (float) $request->validated('nominal'),
                'setoran',
                $enforceMin
            );

            return redirect()->route('tabungan.bukti.show', $result['transaksi_id'])->with('success', 'Setoran berhasil dicatat.');
        } catch (Throwable $e) {
            return $this->gagalProses($e, 'menyimpan setoran tabungan');
        }
    }

    public function storePenarikan(StoreTellerTransactionRequest $request, TellerTransactionService $service): RedirectResponse
    {
        try {
            $result = $service->storeDirect((int) auth()->id(), $request->validated('no_rek'), (float) $request->validated('nominal'), 'penarikan');

            return redirect()->route('tabungan.bukti.show', $result['transaksi_id'])->with('success', 'Penarikan berhasil dicatat.');
        } catch (Throwable $e) {
            return $this->gagalProses($e, 'menyimpan penarikan tabungan');
        }
    }

    public function searchRekening(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));
        $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $results = Rekening::with('nasabah')
            ->where('status_rekening', 'aktif')
            ->when($query !== '', function ($q) use ($query, $likeOp) {
                $q->where(function ($builder) use ($query, $likeOp) {
                    $builder->where('no_rek', $likeOp, "%{$query}%")
                        ->orWhereHas('nasabah', fn ($nasabah) => $nasabah->where('nama_nasabah', $likeOp, "%{$query}%"));
                });
            })
            ->orderBy('no_rek')
            ->limit(50)
            ->get()
            ->map(fn ($rekening) => $this->formatRekeningOption($rekening));

        return response()->json($results);
    }

    public function saldo(Request $request): RedirectResponse
    {
        return redirect()->route('tabungan.mutasi', $request->only('no_rek'));
    }

    public function mutasi(Request $request): View|RedirectResponse
    {
        if ($request->query('mode') === 'koreksi') {
            return redirect()->route('tabungan.koreksi', array_filter([
                'no_rek' => $request->query('no_rek'),
            ]));
        }

        $tab = $request->query('tab') === 'harian' ? 'harian' : 'mutasi';
        $periode = in_array($request->query('periode'), ['semua', 'harian', 'bulanan', 'tahunan'], true)
            ? $request->query('periode')
            : 'semua';
        $logPeriode = in_array($request->query('log_periode'), ['harian', 'bulanan', 'tahunan'], true)
            ? $request->query('log_periode')
            : 'harian';
        $tanggal = $request->query('tanggal', now()->toDateString());
        $bulan = $request->query('bulan', now()->format('Y-m'));
        $tahun = $request->query('tahun', now()->format('Y'));

        $rekening = $this->findAccount($request->query('no_rek'));
        $mutasi = collect();
        $ringkasanMutasi = [
            'total_masuk' => 0.0,
            'total_keluar' => 0.0,
            'jumlah_mutasi' => 0,
            'saldo_akhir' => $rekening ? (float) $rekening->saldo : 0.0,
        ];

        if ($rekening) {
            $baseQuery = $this->mutasiQuery($rekening->rekening_id, $periode, $tanggal, $bulan, $tahun);

            $summaryRow = (clone $baseQuery)
                ->selectRaw('COUNT(*) as jumlah_mutasi, COALESCE(SUM(kredit), 0) as total_masuk, COALESCE(SUM(debit), 0) as total_keluar')
                ->first();

            $ringkasanMutasi = [
                'total_masuk' => (float) ($summaryRow->total_masuk ?? 0),
                'total_keluar' => (float) ($summaryRow->total_keluar ?? 0),
                'jumlah_mutasi' => (int) ($summaryRow->jumlah_mutasi ?? 0),
                'saldo_akhir' => (float) $rekening->saldo,
            ];

            $mutasi = $baseQuery
                ->select('tblTRANSAKSI.*', 'tblJENIS_TRANSAKSI.nama_jenis')
                ->orderByDesc('tblTRANSAKSI.tgl_transaksi')
                ->orderByDesc('tblTRANSAKSI.transaksi_id')
                ->paginate(15)
                ->withQueryString();
        }

        [$useServerSearch, $rekeningOptions] = $this->rekeningDropdownData();
        $selectedOption = $rekening ? $this->formatRekeningOption($rekening) : null;

        // Data untuk tab Log Transaksi Teller (Harian / Bulanan / Tahunan)
        $transaksiHarian = $tab === 'harian'
            ? $this->dailyLogQuery($logPeriode, $tanggal, $bulan, $tahun)->paginate(30)->withQueryString()
            : collect();
        $ringkasanHarian = $tab === 'harian'
            ? $this->dailySummary($logPeriode, $tanggal, $bulan, $tahun)
            : null;
        $logPeriodeLabel = $this->formatLogPeriodeLabel($logPeriode, $tanggal, $bulan, $tahun);

        return view('teller.mutasi', compact(
            'tab',
            'rekening',
            'mutasi',
            'periode',
            'logPeriode',
            'logPeriodeLabel',
            'tanggal',
            'bulan',
            'tahun',
            'ringkasanMutasi',
            'rekeningOptions',
            'useServerSearch',
            'selectedOption',
            'transaksiHarian',
            'ringkasanHarian'
        ));
    }

    public function koreksi(Request $request): View
    {
        $rekening = $this->findAccount($request->query('no_rek'));
        $tanggal = trim((string) $request->query('tanggal', ''));
        $transaksiList = collect();

        $types = DB::table('tblJENIS_TRANSAKSI')
            ->whereIn('nama_jenis', ['setoran', 'penarikan'])
            ->orderBy('jenis_transaksi_id')
            ->get();

        $allTypesMap = DB::table('tblJENIS_TRANSAKSI')
            ->pluck('nama_jenis', 'jenis_transaksi_id');

        if ($rekening) {
            $query = DB::table('tblTRANSAKSI as t')
                ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
                ->leftJoin('tblAUDIT_LOG as al', function ($join) {
                    $join->on('al.entitas_id', '=', 't.transaksi_id')
                        ->where('al.entitas', '=', 'tblTRANSAKSI')
                        ->where('al.aksi', '=', 'KOREKSI_TRANSAKSI');
                })
                ->where('t.rekening_id', $rekening->rekening_id);

            if ($tanggal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $query->whereDate('t.tgl_transaksi', $tanggal);
            }

            $transaksiList = $query
                ->select('t.*', 'jt.nama_jenis', 'al.audit_log_id as koreksi_id')
                ->orderBy('t.tgl_transaksi')
                ->orderBy('t.transaksi_id')
                ->paginate(15)
                ->withQueryString();

            $auditRows = DB::table('tblAUDIT_LOG as al')
                ->join('tblTRANSAKSI as t', 'al.entitas_id', '=', 't.transaksi_id')
                ->where('t.rekening_id', $rekening->rekening_id)
                ->where('al.entitas', 'tblTRANSAKSI')
                ->where('al.aksi', 'KOREKSI_TRANSAKSI')
                ->get(['al.entitas_id', 'al.data_lama', 'al.data_baru']);

            $hasilKoreksiMap = [];
            foreach ($auditRows as $auditRow) {
                $lama = $auditRow->data_lama ? json_decode($auditRow->data_lama, true) : [];
                $baru = $auditRow->data_baru ? json_decode($auditRow->data_baru, true) : [];
                $fallbackReversalLabel = ((float) ($lama['debit'] ?? 0) > 0)
                    ? 'Pembatalan Penarikan'
                    : 'Pembatalan Setoran';

                if (!empty($baru['reversal_id'])) {
                    $hasilKoreksiMap[(int) $baru['reversal_id']] = [
                        'status_koreksi' => 'hasil_koreksi',
                        'keterangan_koreksi' => $baru['reversal_label'] ?? $fallbackReversalLabel,
                    ];
                }
                if (!empty($baru['corrected_id'])) {
                    $hasilKoreksiMap[(int) $baru['corrected_id']] = [
                        'status_koreksi' => 'hasil_koreksi',
                        'keterangan_koreksi' => $baru['corrected_label'] ?? null,
                    ];
                }
            }

            $transaksiList->getCollection()->transform(function ($item) use ($hasilKoreksiMap) {
                $tid = (int) $item->transaksi_id;
                if ($item->koreksi_id) {
                    $item->status_koreksi = 'sudah_dikoreksi';
                } elseif (isset($hasilKoreksiMap[$tid])) {
                    $item->status_koreksi = $hasilKoreksiMap[$tid]['status_koreksi'];
                    $item->keterangan_koreksi = $hasilKoreksiMap[$tid]['keterangan_koreksi'];
                } else {
                    $item->status_koreksi = 'normal';
                }
                return $item;
            });
        }

        $riwayatKoreksi = DB::table('tblAUDIT_LOG as al')
            ->join('tblTRANSAKSI as t', 'al.entitas_id', '=', 't.transaksi_id')
            ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->leftJoin('tblUSER as u', 'al.user_id', '=', 'u.user_id')
            ->where('al.entitas', 'tblTRANSAKSI')
            ->where('al.aksi', 'KOREKSI_TRANSAKSI')
            ->when($rekening, fn ($q) => $q->where('t.rekening_id', $rekening->rekening_id))
            ->select(
                'al.audit_log_id',
                'al.created_at',
                'al.data_lama',
                'al.data_baru',
                'r.no_rek',
                'n.nama_nasabah',
                'u.nama_user as nama_teller'
            )
            ->orderByDesc('al.created_at')
            ->orderByDesc('al.audit_log_id')
            ->limit(20)
            ->get()
            ->map(function ($row) use ($allTypesMap) {
                $lama = $row->data_lama ? json_decode($row->data_lama, true) : [];
                $baru = $row->data_baru ? json_decode($row->data_baru, true) : [];

                $debitLama = (float) ($lama['debit'] ?? 0);
                $kreditLama = (float) ($lama['kredit'] ?? 0);
                $nominalLama = $debitLama > 0 ? $debitLama : $kreditLama;
                $jenisLama = $debitLama > 0 ? 'Penarikan' : 'Setoran / Kredit';

                $jenisBaruId = $baru['jenis_transaksi_id'] ?? null;
                $jenisBaru = $jenisBaruId && isset($allTypesMap[$jenisBaruId])
                    ? ucfirst(str_replace('_', ' ', $allTypesMap[$jenisBaruId]))
                    : '-';
                $nominalBaru = (float) ($baru['nominal'] ?? 0);

                return (object) [
                    'waktu_koreksi' => $row->created_at,
                    'no_rek' => $row->no_rek,
                    'nama_nasabah' => $row->nama_nasabah,
                    'sebelum' => $jenisLama . ' Rp ' . number_format($nominalLama, 0, ',', '.'),
                    'sesudah' => $jenisBaru . ' Rp ' . number_format($nominalBaru, 0, ',', '.'),
                    'alasan' => $baru['alasan'] ?? '-',
                    'nama_teller' => $row->nama_teller ?? '-',
                ];
            });

        [$useServerSearch, $rekeningOptions] = $this->rekeningDropdownData();
        $selectedOption = $rekening ? $this->formatRekeningOption($rekening) : null;

        return view('teller.koreksi', compact(
            'rekening',
            'tanggal',
            'types',
            'transaksiList',
            'riwayatKoreksi',
            'rekeningOptions',
            'useServerSearch',
            'selectedOption'
        ));
    }

    public function logHarian(Request $request): RedirectResponse
    {
        return redirect()->route('tabungan.mutasi', array_filter([
            'tab' => 'harian',
            'log_periode' => $request->query('log_periode'),
            'tanggal' => $request->query('tanggal'),
            'bulan' => $request->query('bulan'),
            'tahun' => $request->query('tahun'),
        ]));
    }

    public function printLogHarian(Request $request): View
    {
        $logPeriode = in_array($request->query('log_periode'), ['harian', 'bulanan', 'tahunan'], true)
            ? $request->query('log_periode')
            : 'harian';
        $tanggal = $request->query('tanggal', now()->toDateString());
        $bulan = $request->query('bulan', now()->format('Y-m'));
        $tahun = $request->query('tahun', now()->format('Y'));

        $transaksi = $this->dailyLogQuery($logPeriode, $tanggal, $bulan, $tahun)->get();
        $ringkasan = $this->dailySummary($logPeriode, $tanggal, $bulan, $tahun);
        $logPeriodeLabel = $this->formatLogPeriodeLabel($logPeriode, $tanggal, $bulan, $tahun);

        return view('teller.log_transaksi_cetak', compact(
            'logPeriode',
            'logPeriodeLabel',
            'tanggal',
            'bulan',
            'tahun',
            'transaksi',
            'ringkasan'
        ));
    }

    public function printMutasi(Request $request): View
    {
        $rekening = $this->findAccount($request->query('no_rek'));
        abort_unless($rekening, 404);

        $periode = in_array($request->query('periode'), ['semua', 'harian', 'bulanan', 'tahunan'], true)
            ? $request->query('periode')
            : 'semua';
        $tanggal = $request->query('tanggal', now()->toDateString());
        $bulan = $request->query('bulan', now()->format('Y-m'));
        $tahun = $request->query('tahun', now()->format('Y'));

        $mutasi = $this->mutasiQuery($rekening->rekening_id, $periode, $tanggal, $bulan, $tahun)
            ->select('tblTRANSAKSI.*', 'tblJENIS_TRANSAKSI.nama_jenis')
            ->orderBy('tblTRANSAKSI.tgl_transaksi')
            ->orderBy('tblTRANSAKSI.transaksi_id')
            ->get();

        $ringkasanMutasi = [
            'total_masuk' => (float) $mutasi->sum('kredit'),
            'total_keluar' => (float) $mutasi->sum('debit'),
            'jumlah_mutasi' => $mutasi->count(),
            'saldo_akhir' => (float) $rekening->saldo,
        ];

        $periodeLabel = match ($periode) {
            'harian' => 'Harian (' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') . ')',
            'bulanan' => 'Bulanan (' . \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y') . ')',
            'tahunan' => 'Tahunan (' . $tahun . ')',
            default => 'Semua Periode',
        };

        return view('teller.mutasi_cetak', compact('rekening', 'mutasi', 'ringkasanMutasi', 'periodeLabel', 'periode', 'tanggal', 'bulan', 'tahun'));
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

    private function rekeningDropdownData(): array
    {
        $totalRekeningAktif = Rekening::where('status_rekening', 'aktif')->count();
        $useServerSearch = $totalRekeningAktif > 500;
        $rekeningOptions = $useServerSearch
            ? collect()
            : Rekening::with('nasabah')
                ->where('status_rekening', 'aktif')
                ->orderBy('no_rek')
                ->get()
                ->map(fn ($r) => $this->formatRekeningOption($r));

        return [$useServerSearch, $rekeningOptions];
    }

    private function formatRekeningOption(Rekening $rekening): array
    {
        $nasabah = $rekening->nasabah;
        $peran = $nasabah?->jenis_nasabah === 'pegawai' ? 'Pegawai' : 'Siswa';
        $kelasUnit = $nasabah?->kelas_unit ? ' / ' . $nasabah->kelas_unit : '';
        $saldoFormatted = number_format((float) $rekening->saldo, 0, ',', '.');

        return [
            'no_rek' => $rekening->no_rek,
            'nama_nasabah' => $nasabah?->nama_nasabah ?? '-',
            'jenis_nasabah' => $nasabah?->jenis_nasabah ?? 'non_pegawai',
            'kelas_unit' => $nasabah?->kelas_unit,
            'saldo' => (float) $rekening->saldo,
            'label' => "{$rekening->no_rek} - " . ($nasabah?->nama_nasabah ?? '-') . " ({$peran}{$kelasUnit}) — Rp {$saldoFormatted}",
        ];
    }

    private function mutasiQuery(int $rekeningId, string $periode, string $tanggal, string $bulan, string $tahun)
    {
        $query = DB::table('tblTRANSAKSI')
            ->join('tblJENIS_TRANSAKSI', 'tblTRANSAKSI.jenis_transaksi_id', '=', 'tblJENIS_TRANSAKSI.jenis_transaksi_id')
            ->where('tblTRANSAKSI.rekening_id', $rekeningId);

        if ($periode === 'harian' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $query->whereDate('tblTRANSAKSI.tgl_transaksi', $tanggal);
        } elseif ($periode === 'bulanan' && preg_match('/^(\d{4})-(\d{2})$/', $bulan, $m)) {
            $query->whereYear('tblTRANSAKSI.tgl_transaksi', (int) $m[1])
                ->whereMonth('tblTRANSAKSI.tgl_transaksi', (int) $m[2]);
        } elseif ($periode === 'tahunan' && preg_match('/^\d{4}$/', $tahun)) {
            $query->whereYear('tblTRANSAKSI.tgl_transaksi', (int) $tahun);
        }

        return $query;
    }

    private function activeAccounts()
    {
        return Rekening::with('nasabah')
            ->where('status_rekening', 'aktif')
            ->orderBy('no_rek')
            ->limit(100)
            ->get();
    }

    private function findAccount(?string $noRek)
    {
        if (!$noRek) {
            return null;
        }

        return Rekening::with('nasabah')->where('no_rek', $noRek)->where('status_rekening', 'aktif')->first();
    }

    private function applyPeriodFilter($query, string $column, string $periode, string $tanggal, string $bulan, string $tahun)
    {
        if ($periode === 'harian' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $query->whereDate($column, $tanggal);
        } elseif ($periode === 'bulanan' && preg_match('/^(\d{4})-(\d{2})$/', $bulan, $m)) {
            $query->whereYear($column, (int) $m[1])
                ->whereMonth($column, (int) $m[2]);
        } elseif ($periode === 'tahunan' && preg_match('/^\d{4}$/', $tahun)) {
            $query->whereYear($column, (int) $tahun);
        }

        return $query;
    }

    private function formatLogPeriodeLabel(string $logPeriode, string $tanggal, string $bulan, string $tahun): string
    {
        return match ($logPeriode) {
            'bulanan' => preg_match('/^\d{4}-\d{2}$/', $bulan)
                ? 'Bulanan (' . \Carbon\Carbon::createFromFormat('Y-m', $bulan)->translatedFormat('F Y') . ')'
                : 'Bulanan (' . $bulan . ')',
            'tahunan' => 'Tahunan (' . $tahun . ')',
            default => preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)
                ? 'Harian (' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') . ')'
                : 'Harian (' . $tanggal . ')',
        };
    }

    private function dailyLogQuery(string $logPeriode, string $tanggal, string $bulan, string $tahun)
    {
        $query = DB::table('tblTRANSAKSI as t')
            ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
            ->leftJoin('tblUSER as u', 't.user_id', '=', 'u.user_id')
            ->leftJoin('tblAUDIT_LOG as al', function ($join) {
                $join->on('al.entitas_id', '=', 't.transaksi_id')
                    ->where('al.entitas', '=', 'tblTRANSAKSI')
                    ->where('al.aksi', '=', 'KOREKSI_TRANSAKSI');
            });

        $this->applyPeriodFilter($query, 't.tgl_transaksi', $logPeriode, $tanggal, $bulan, $tahun);

        return $query->select(
                't.*',
                'r.no_rek',
                'n.nama_nasabah',
                'jt.nama_jenis',
                'u.nama_user as nama_pencatat',
                'al.audit_log_id as koreksi_id'
            )
            ->orderBy('t.tgl_transaksi');
    }

    private function dailySummary(string $logPeriode, string $tanggal, string $bulan, string $tahun): array
    {
        $summaryQuery = DB::table('tblTRANSAKSI');
        $this->applyPeriodFilter($summaryQuery, 'tgl_transaksi', $logPeriode, $tanggal, $bulan, $tahun);
        $summary = $summaryQuery
            ->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        $bungaQuery = DB::table('tblTRANSAKSI as t')
            ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
            ->where('jt.nama_jenis', 'jasa_tabungan');
        $this->applyPeriodFilter($bungaQuery, 't.tgl_transaksi', $logPeriode, $tanggal, $bulan, $tahun);

        $koreksiQuery = DB::table('tblAUDIT_LOG')
            ->where('aksi', 'KOREKSI_TRANSAKSI')
            ->where('entitas', 'tblTRANSAKSI');
        $this->applyPeriodFilter($koreksiQuery, 'created_at', $logPeriode, $tanggal, $bulan, $tahun);

        return [
            'total_transaksi' => (int) ($summary->total_transaksi ?? 0),
            'total_debit' => (float) ($summary->total_debit ?? 0),
            'total_kredit' => (float) ($summary->total_kredit ?? 0),
            'total_bunga' => (float) $bungaQuery->sum('t.kredit'),
            'total_koreksi' => (int) $koreksiQuery->count(),
        ];
    }
}