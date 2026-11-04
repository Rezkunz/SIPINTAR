<?php

namespace App\Http\Controllers\SiswaPraktik;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiswaPraktik\ApproveRekeningRequest;
use App\Http\Requests\SiswaPraktik\BukaRekeningRequest;
use App\Models\Rekening;
use App\Services\RekeningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BukaRekeningController extends Controller
{
    /**
     * Tampilkan formulir pembukaan rekening baru bagi siswa praktik.
     */
    public function create(): View
    {
        $pendingRekening = Rekening::with('nasabah')
            ->where('status_rekening', 'pending')
            ->orderByDesc('rekening_id')
            ->paginate(10);

        return view('siswa_praktik.buka_rekening', compact('pendingRekening'));
    }

    /**
     * Simpan data pembukaan rekening baru.
     */
    public function store(BukaRekeningRequest $request, RekeningService $rekeningService): RedirectResponse
    {
        $rekening = $rekeningService->bukaRekening($request->validated());

        return redirect()->route('tabungan.buka-rekening')->with([
            'success_rekening' => $rekening->no_rek,
            'success_nama'     => $rekening->nasabah->nama_nasabah,
            'status'           => "Rekening {$rekening->no_rek} menunggu setoran awal minimal Rp 10.000 untuk diaktifkan.",
        ]);
    }

    public function approve(ApproveRekeningRequest $request, RekeningService $rekeningService, Rekening $rekening): RedirectResponse
    {
        $rekeningService->aktifkanDenganSetoran(
            $rekening->rekening_id,
            (int) Auth::id(),
            (float) $request->validated('setoran_awal')
        );

        return redirect()->route('tabungan.buka-rekening')->with('status', 'Rekening berhasil disetujui dan diaktifkan oleh Siswa Praktik.');
    }
}
