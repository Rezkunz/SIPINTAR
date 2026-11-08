<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Services\TellerInterestService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TellerInterestController extends Controller
{
    /**
     * Tampilkan kalkulator bunga tabungan dan status periode berjalan.
     */
    public function index(Request $request, TellerInterestService $service): View
    {
        $accounts = $service->activeAccounts();
        $selected = $service->preview($request->query('no_rek'));

        return view('teller.bunga_tabungan', compact('accounts', 'selected'));
    }
}
