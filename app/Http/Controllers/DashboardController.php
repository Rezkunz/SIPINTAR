<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Bendahara\BendaharaDashboardController;
use App\Http\Controllers\BendaharaMini\BendaharaMiniDashboardController;
use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\Nasabah\NasabahDashboardController;
use App\Http\Controllers\SiswaPraktik\SiswaPraktikDashboardController;
use App\Http\Controllers\Teller\TellerDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama sesuai role user melalui Controller masing-masing aktor.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        return match ($user->role) {
            'manager'        => app(ManagerDashboardController::class)->index(),
            'teller'         => app(TellerDashboardController::class)->index(),
            'bendahara'      => app(BendaharaDashboardController::class)->index(),
            'bendahara_mini' => app(BendaharaMiniDashboardController::class)->index(),
            'siswa_praktik'  => app(SiswaPraktikDashboardController::class)->index(),
            'nasabah'        => app(NasabahDashboardController::class)->index(),
            default          => app(ManagerDashboardController::class)->index(),
        };
    }
}
