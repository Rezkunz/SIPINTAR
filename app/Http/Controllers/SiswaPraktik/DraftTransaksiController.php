<?php

namespace App\Http\Controllers\SiswaPraktik;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiswaPraktik\StoreDraftRequest;
use App\Models\Draft;
use App\Models\Rekening;
use App\Services\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DraftTransaksiController extends Controller
{
    /**
     * Tampilkan formulir pembuatan draft setoran / penarikan.
     */
    public function create(Request $request): View
    {
        $jenis = strtolower($request->query('jenis', 'setoran'));
        if (!in_array($jenis, ['setoran', 'penarikan'], true)) {
            $jenis = 'setoran';
        }

        // Ambil daftar rekening aktif untuk kemudahan pilihan cepat
        $rekeningList = Rekening::with('nasabah')
            ->where('status_rekening', 'aktif')
            ->whereHas('nasabah', function ($q) {
                $q->where('status_nasabah', 'aktif');
            })
            ->orderBy('no_rek', 'asc')
            ->limit(30)
            ->get();

        $selectedRekening = null;
        if ($request->filled('no_rek')) {
            $selectedRekening = Rekening::with('nasabah')
                ->where('no_rek', $request->query('no_rek'))
                ->where('status_rekening', 'aktif')
                ->first();
        }

        return view('siswa_praktik.draft_form', compact('jenis', 'rekeningList', 'selectedRekening'));
    }

    /**
     * Endpoint API pencarian rekening aktif (nomor rekening atau nama nasabah).
     */
    public function searchRekening(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $rekening = Rekening::with('nasabah')
            ->where('status_rekening', 'aktif')
            ->whereHas('nasabah', function ($q) {
                $q->where('status_nasabah', 'aktif');
            })
            ->where(function ($q) use ($query) {
                $q->where('no_rek', 'like', "%{$query}%")
                  ->orWhereHas('nasabah', function ($sub) use ($query) {
                      $sub->where('nama_nasabah', 'like', "%{$query}%");
                  });
            })
            ->limit(10)
            ->get()
            ->map(function ($rek) {
                return [
                    'rekening_id'     => $rek->rekening_id,
                    'no_rek'          => $rek->no_rek,
                    'nama_nasabah'    => $rek->nasabah->nama_nasabah ?? '-',
                    'jenis_nasabah'   => $rek->nasabah->jenis_nasabah ?? '-',
                    'kelas_unit'      => $rek->nasabah->kelas_unit ?? '-',
                    'saldo'           => (float) $rek->saldo,
                    'saldo_formatted' => 'Rp ' . number_format($rek->saldo, 0, ',', '.'),
                    'status_rekening' => $rek->status_rekening,
                ];
            });

        return response()->json($rekening);
    }

    /**
     * Simpan entri draft transaksi baru.
     */
    public function store(StoreDraftRequest $request, DraftService $draftService): RedirectResponse
    {
        $draft = $draftService->createDraft(Auth::id(), $request->validated());

        return redirect()->route('tabungan.draft.riwayat')->with([
            'success_draft' => $draft->draft_id,
            'status'        => 'Draft transaksi berhasil disimpan dengan status menunggu verifikasi loket.',
        ]);
    }

    /**
     * Tampilkan riwayat draft milik siswa praktik yang sedang login.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $statusFilter = strtolower($request->query('status', 'semua'));

        $query = Draft::with(['rekening.nasabah', 'jenisTransaksi', 'teller'])
            ->where('siswa_id', $userId);

        if (in_array($statusFilter, ['pending', 'disetujui', 'ditolak'], true)) {
            $query->where('status_verifikasi', $statusFilter);
        }

        $drafts = $query->orderBy('tgl_input', 'desc')->paginate(10)->withQueryString();

        // Ringkasan metrik statistik milik siswa sendiri
        $totalSemua = Draft::where('siswa_id', $userId)->count();
        $totalPending = Draft::where('siswa_id', $userId)->where('status_verifikasi', 'pending')->count();
        $totalDisetujui = Draft::where('siswa_id', $userId)->where('status_verifikasi', 'disetujui')->count();
        $totalDitolak = Draft::where('siswa_id', $userId)->where('status_verifikasi', 'ditolak')->count();

        return view('siswa_praktik.riwayat_draft', compact(
            'drafts',
            'statusFilter',
            'totalSemua',
            'totalPending',
            'totalDisetujui',
            'totalDitolak'
        ));
    }

    /**
     * Tampilkan detail sebuah draft (diproteksi agar tidak terjadi IDOR).
     */
    public function show(Draft $draft): View
    {
        // Proteksi IDOR: Siswa hanya boleh melihat draft buatannya sendiri
        if ((int) $draft->siswa_id !== (int) Auth::id()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melihat draft transaksi milik siswa lain.');
        }

        $draft->load(['rekening.nasabah', 'jenisTransaksi', 'teller', 'siswa']);

        return view('siswa_praktik.draft_detail', compact('draft'));
    }

    /**
     * Handler rute /tabungan/setoran:
     * Siswa praktik diarahkan ke form draft setoran.
     * Teller tetap melihat placeholder asli.
     */
    public function handleSetoran(Request $request)
    {
        if (Auth::user()->hasRole('siswa_praktik')) {
            return redirect()->route('tabungan.draft', ['jenis' => 'setoran']);
        }

        return view('placeholder', [
            'title'    => 'Catat Setoran Tabungan',
            'subtitle' => 'Form setoran tabungan nasabah (Loket Operasional Teller)',
        ]);
    }

    /**
     * Handler rute /tabungan/penarikan:
     * Siswa praktik diarahkan ke form draft penarikan.
     * Teller tetap melihat placeholder asli.
     */
    public function handlePenarikan(Request $request)
    {
        if (Auth::user()->hasRole('siswa_praktik')) {
            return redirect()->route('tabungan.draft', ['jenis' => 'penarikan']);
        }

        return view('placeholder', [
            'title'    => 'Catat Penarikan Tabungan',
            'subtitle' => 'Form penarikan saldo tabungan (Loket Operasional Teller)',
        ]);
    }
}
