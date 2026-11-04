<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Services\TellerInterestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TellerInterestController extends Controller
{
    public function index(Request $request, TellerInterestService $service): View
    {
        $accounts = $service->activeAccounts();
        $selected = $service->preview($request->query('no_rek'));

        return view('teller.bunga_tabungan', compact('accounts', 'selected'));
    }

    public function process(int $rekening, TellerInterestService $service): RedirectResponse
    {
        $service->process($rekening, (int) auth()->id());

        return redirect()->route('tabungan.jasa')->with('success', 'Bunga tabungan berhasil diproses untuk rekening yang dipilih.');
    }
}