<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\CorrectTransactionRequest;
use App\Services\TellerCorrectionService;
use Illuminate\Http\RedirectResponse;

class TellerCorrectionController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('tabungan.mutasi', ['mode' => 'koreksi']);
    }

    public function correct(CorrectTransactionRequest $request, int $transaksi, TellerCorrectionService $service): RedirectResponse
    {
        $service->correct(
            $transaksi,
            (int) auth()->id(),
            $request->validated('alasan'),
            (int) $request->validated('jenis_transaksi_id'),
            (float) $request->validated('nominal')
        );

        return redirect()->route('tabungan.mutasi', [
            'no_rek' => $request->input('no_rek'),
            'mode' => 'koreksi',
        ])->with('success', 'Transaksi berhasil dikoreksi melalui transaksi baru dan pembalik.');
    }
}