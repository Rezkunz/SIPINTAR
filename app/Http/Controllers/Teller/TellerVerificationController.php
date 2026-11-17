<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Concerns\TanganiKesalahanInput;
use App\Http\Controllers\Controller;
use App\Models\Draft;
use App\Services\TellerTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class TellerVerificationController extends Controller
{
    use TanganiKesalahanInput;

    public function index(): View
    {
        $drafts = Draft::with(['rekening.nasabah', 'jenisTransaksi', 'siswa'])
            ->where('status_verifikasi', 'pending')
            ->orderBy('tgl_input')
            ->paginate(15);

        return view('teller.verifikasi', compact('drafts'));
    }

    public function show(Draft $draft): View
    {
        $draft->load(['rekening.nasabah', 'jenisTransaksi', 'siswa']);

        return view('teller.verifikasi_detail', compact('draft'));
    }

    public function approve(Draft $draft, TellerTransactionService $service): RedirectResponse
    {
        try {
            $service->approveDraft($draft->draft_id, (int) auth()->id());

            return redirect()->route('tabungan.verifikasi')->with('success', 'Draft berhasil disetujui dan transaksi telah dicatat.');
        } catch (Throwable $e) {
            return $this->gagalProses($e, 'menyetujui draft transaksi');
        }
    }

    public function reject(Draft $draft, TellerTransactionService $service): RedirectResponse
    {
        try {
            $service->rejectDraft($draft->draft_id, (int) auth()->id());

            return redirect()->route('tabungan.verifikasi')->with('success', 'Draft berhasil ditolak.');
        } catch (Throwable $e) {
            return $this->gagalProses($e, 'menolak draft transaksi');
        }
    }
}