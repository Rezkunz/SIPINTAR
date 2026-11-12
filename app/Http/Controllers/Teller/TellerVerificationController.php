<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use App\Services\TellerTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TellerVerificationController extends Controller
{
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
        $service->approveDraft($draft->draft_id, (int) auth()->id());

        return redirect()->route('tabungan.verifikasi')->with('success', 'Draft berhasil disetujui dan transaksi telah dicatat.');
    }

    public function reject(Request $request, Draft $draft, TellerTransactionService $service): RedirectResponse
    {
        $data = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'max:500'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
            'alasan_penolakan.max'      => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $service->rejectDraft($draft->draft_id, (int) auth()->id(), $data['alasan_penolakan']);

        return redirect()->route('tabungan.verifikasi')->with('success', 'Draft berhasil ditolak.');
    }
}