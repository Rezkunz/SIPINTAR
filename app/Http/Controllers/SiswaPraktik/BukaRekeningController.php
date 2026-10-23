<?php

namespace App\Http\Controllers\SiswaPraktik;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiswaPraktik\BukaRekeningRequest;
use App\Services\RekeningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BukaRekeningController extends Controller
{
    /**
     * Tampilkan formulir pembukaan rekening baru bagi siswa praktik.
     */
    public function create(): View
    {
        return view('siswa_praktik.buka_rekening');
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
            'status'           => "Rekening berhasil dibuka dengan Nomor Rekening: {$rekening->no_rek}",
        ]);
    }
}
