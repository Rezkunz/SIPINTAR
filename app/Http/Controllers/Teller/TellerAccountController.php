<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Rekening;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\RekeningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TellerAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = in_array($request->query('status'), ['aktif', 'pending', 'blokir', 'tutup'], true)
            ? $request->query('status')
            : null;

        $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $rekeningList = Rekening::with(['nasabah.user'])
            ->when($status, fn ($q) => $q->where('status_rekening', $status))
            ->when($search !== '', function ($q) use ($search, $likeOp) {
                $q->where(function ($sub) use ($search, $likeOp) {
                    $sub->where('no_rek', $likeOp, "%{$search}%")
                        ->orWhereHas('nasabah', fn ($n) => $n
                            ->where('nama_nasabah', $likeOp, "%{$search}%")
                            ->orWhere('no_hp', $likeOp, "%{$search}%"));
                });
            })
            ->orderByDesc('rekening_id')
            ->paginate(15)
            ->withQueryString();

        return view('teller.kelola_akun.index', compact('rekeningList', 'search', 'status'));
    }

    public function show(Rekening $rekening): View
    {
        $rekening->load(['nasabah.user']);

        return view('teller.kelola_akun.show', compact('rekening'));
    }

    public function updateProfil(Request $request, Rekening $rekening): RedirectResponse
    {
        $nasabah = $rekening->nasabah;
        abort_unless($nasabah, 404);

        $validated = $request->validate([
            'nama_nasabah'  => ['required', 'string', 'max:100'],
            'no_hp'         => [
                'required',
                'string',
                'max:15',
                Rule::unique('tblNASABAH', 'no_hp')->ignore($nasabah->nasabah_id, 'nasabah_id'),
            ],
            'jenis_nasabah' => ['required', Rule::in(['pegawai', 'non_pegawai'])],
            'kelas_unit'    => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($nasabah, $validated) {
            $dataLama = [
                'nama_nasabah'  => $nasabah->nama_nasabah,
                'no_hp'         => $nasabah->no_hp,
                'jenis_nasabah' => $nasabah->jenis_nasabah,
                'kelas_unit'    => $nasabah->kelas_unit,
            ];

            $nasabah->update([
                'nama_nasabah'  => trim($validated['nama_nasabah']),
                'no_hp'         => trim($validated['no_hp']),
                'jenis_nasabah' => $validated['jenis_nasabah'],
                'kelas_unit'    => !empty($validated['kelas_unit']) ? trim($validated['kelas_unit']) : null,
            ]);

            if ($nasabah->user) {
                $nasabah->user->update([
                    'nama_user' => trim($validated['nama_nasabah']),
                    'no_hp'     => trim($validated['no_hp']),
                ]);
            }

            AuditLogService::log(
                aksi: 'EDIT_PROFIL_NASABAH',
                entitas: 'tblNASABAH',
                entitasId: $nasabah->nasabah_id,
                dataLama: $dataLama,
                dataBaru: $validated
            );
        });

        return redirect()
            ->route('tabungan.kelola-akun.show', $rekening->rekening_id)
            ->with('success', 'Profil nasabah berhasil diperbarui.');
    }

    public function resetPassword(Rekening $rekening): RedirectResponse
    {
        $nasabah = $rekening->nasabah;
        abort_unless($nasabah, 404);

        $plainPassword = Str::upper(Str::random(4)) . random_int(1000, 9999);

        DB::transaction(function () use ($nasabah, $plainPassword) {
            $user = $nasabah->user;

            if ($user) {
                $user->update(['password' => Hash::make($plainPassword)]);
            } else {
                $user = User::create([
                    'role_id'     => 6,
                    'nasabah_id'  => $nasabah->nasabah_id,
                    'no_hp'       => $nasabah->no_hp,
                    'nama_user'   => $nasabah->nama_nasabah,
                    'password'    => Hash::make($plainPassword),
                    'status_akun' => 'aktif',
                ]);
            }

            AuditLogService::log(
                aksi: 'RESET_PASSWORD_NASABAH',
                entitas: 'tblUSER',
                entitasId: $user->user_id,
                dataBaru: [
                    'nasabah_id' => $nasabah->nasabah_id,
                    'rekening_id' => $nasabah->rekening?->rekening_id,
                ]
            );
        });

        return redirect()
            ->route('tabungan.kelola-akun.show', $rekening->rekening_id)
            ->with('reset_password_once', $plainPassword)
            ->with('success', 'Password/PIN nasabah berhasil direset. Salin nilai sementara di bawah ini (hanya tampil sekali).');
    }

    public function tutupRekening(Request $request, Rekening $rekening, RekeningService $service): RedirectResponse
    {
        $validated = $request->validate([
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $service->tutupRekening(
            $rekening->rekening_id,
            (int) auth()->id(),
            $validated['alasan'] ?? null
        );

        return redirect()
            ->route('tabungan.kelola-akun.bukti-tutup', $rekening->rekening_id)
            ->with('success', 'Rekening berhasil ditutup.')
            ->with('saldo_dicairkan', $result['saldo_dicairkan']);
    }

    public function buktiTutup(Rekening $rekening): View
    {
        $rekening->load('nasabah');

        $audit = DB::table('tblAUDIT_LOG')
            ->leftJoin('tblUSER', 'tblAUDIT_LOG.user_id', '=', 'tblUSER.user_id')
            ->where('tblAUDIT_LOG.entitas', 'tblREKENING')
            ->where('tblAUDIT_LOG.entitas_id', $rekening->rekening_id)
            ->where('tblAUDIT_LOG.aksi', 'TUTUP_REKENING')
            ->orderByDesc('tblAUDIT_LOG.audit_log_id')
            ->select('tblAUDIT_LOG.*', 'tblUSER.nama_user as nama_teller')
            ->first();

        $dataBaru = $audit && $audit->data_baru ? json_decode($audit->data_baru, true) : [];
        $dataLama = $audit && $audit->data_lama ? json_decode($audit->data_lama, true) : [];

        return view('teller.kelola_akun.bukti_tutup', compact('rekening', 'audit', 'dataBaru', 'dataLama'));
    }
}
