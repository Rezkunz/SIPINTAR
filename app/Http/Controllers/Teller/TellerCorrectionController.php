<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\CorrectTransactionRequest;
use App\Services\TellerCorrectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TellerCorrectionController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('tabungan.mutasi', ['mode' => 'koreksi']);
    }

    /**
     * Audit log koreksi transaksi tabungan: nilai lama, nilai baru, alasan, pengguna, dan waktu.
     */
    public function auditLog(Request $request): View
    {
        $noRek = trim((string) $request->query('no_rek', ''));

        $logs = DB::table('tblAUDIT_LOG as al')
            ->leftJoin('tblUSER as u', 'al.user_id', '=', 'u.user_id')
            ->leftJoin('tblTRANSAKSI as t', 'al.entitas_id', '=', 't.transaksi_id')
            ->leftJoin('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->leftJoin('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->leftJoin('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
            ->where('al.entitas', 'tblTRANSAKSI')
            ->where('al.aksi', 'KOREKSI_TRANSAKSI')
            ->when($noRek !== '', fn ($q) => $q->where('r.no_rek', $noRek))
            ->select(
                'al.*',
                'u.nama_user',
                'r.no_rek',
                'n.nama_nasabah',
                'jt.nama_jenis as jenis_lama'
            )
            ->orderByDesc('al.created_at')
            ->orderByDesc('al.audit_log_id')
            ->paginate(20)
            ->withQueryString();

        $namaJenis = DB::table('tblJENIS_TRANSAKSI')->pluck('nama_jenis', 'jenis_transaksi_id');

        return view('teller.audit_log', compact('logs', 'noRek', 'namaJenis'));
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