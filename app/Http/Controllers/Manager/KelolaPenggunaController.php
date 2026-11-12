<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Manajemen akun pengguna oleh Manager (PRD §2: "Manajemen User").
 *
 * - Akun petugas (Teller, Bendahara, Bendahara Bank Mini, Manager, Siswa Praktik) dibuat dan diubah di sini.
 * - Akun Nasabah terbentuk lewat pendaftaran + rekening, sehingga di sini hanya dapat
 *   direset kata sandinya dan diaktifkan/dinonaktifkan; identitasnya mengikuti data nasabah.
 * - Akun tidak dihapus karena tblUSER dirujuk transaksi (ON DELETE CASCADE); gunakan status nonaktif.
 */
class KelolaPenggunaController extends Controller
{
    /** slug role => [role_id, label, prefix kode pegawai] sesuai tblROLE & PRD */
    public const ROLE = [
        'manager'        => ['id' => 4, 'label' => 'Manager',             'prefix' => 'MGR'],
        'bendahara_mini' => ['id' => 3, 'label' => 'Bendahara Bank Mini', 'prefix' => 'BBM'],
        'bendahara'      => ['id' => 2, 'label' => 'Bendahara',           'prefix' => 'BND'],
        'teller'         => ['id' => 1, 'label' => 'Teller',              'prefix' => 'TLR'],
        'siswa_praktik'  => ['id' => 5, 'label' => 'Siswa Praktik',       'prefix' => null],
        'nasabah'        => ['id' => 6, 'label' => 'Nasabah',             'prefix' => null],
    ];

    /** Role yang akunnya dapat dibuat oleh Manager */
    public const ROLE_PETUGAS = ['manager', 'bendahara_mini', 'bendahara', 'teller', 'siswa_praktik'];

    public function index(Request $request)
    {
        $filterRole   = array_key_exists($request->query('role'), self::ROLE) ? $request->query('role') : null;
        $filterStatus = in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null;
        $cari         = trim((string) $request->query('cari', ''));

        $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $pengguna = User::query()
            ->with('nasabah')
            ->when($filterRole, fn ($q) => $q->where('role_id', self::ROLE[$filterRole]['id']))
            ->when($filterStatus, fn ($q) => $q->where('status_akun', $filterStatus))
            ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_user', $likeOp, "%{$cari}%")
                ->orWhere('no_induk', $likeOp, "%{$cari}%")
                ->orWhere('no_hp', $likeOp, "%{$cari}%")
                ->orWhere('email', $likeOp, "%{$cari}%")))
            ->orderBy('role_id')
            ->orderBy('nama_user')
            ->paginate(15)
            ->withQueryString();

        $rekap = DB::table('tblUSER')
            ->select('role_id', 'status_akun', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('role_id', 'status_akun')
            ->get();

        $jumlahPerRole = collect(self::ROLE)->map(fn ($r) => (int) $rekap->where('role_id', $r['id'])->sum('jumlah'));

        $diubah = $request->filled('edit') ? User::find($request->integer('edit')) : null;

        return view('manager.pengguna.index', [
            'pengguna'       => $pengguna,
            'filterRole'     => $filterRole,
            'filterStatus'   => $filterStatus,
            'cari'           => $cari,
            'jumlahPerRole'  => $jumlahPerRole,
            'totalAktif'     => (int) $rekap->where('status_akun', 'aktif')->sum('jumlah'),
            'totalNonaktif'  => (int) $rekap->where('status_akun', 'nonaktif')->sum('jumlah'),
            'totalPetugas'   => (int) $rekap->whereNotIn('role_id', [self::ROLE['nasabah']['id']])->sum('jumlah'),
            'diubah'         => $diubah,
            'saranKode'      => collect(self::ROLE)->filter(fn ($r) => $r['prefix'])->map(fn ($r) => $this->kodeBerikutnya($r['prefix'])),
        ]);
    }

    /**
     * Tambah akun petugas baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $roleSlug = $data['role'];
        $noInduk = $data['no_induk'] ?: $this->kodeBerikutnya(self::ROLE[$roleSlug]['prefix']);

        $user = User::create([
            'role_id'     => self::ROLE[$roleSlug]['id'],
            'nasabah_id'  => null,
            'nama_user'   => $data['nama_user'],
            'no_induk'    => $noInduk,
            'no_hp'       => $data['no_hp'],
            'email'       => $data['email'] ?: null,
            'password'    => Hash::make($data['password']),
            'status_akun' => 'aktif',
        ]);

        AuditLogService::log(aksi: 'INSERT', entitas: 'tblUSER', entitasId: $user->user_id, dataBaru: $this->jejak($user));

        return redirect()
            ->route('akun.kelola', ['role' => $roleSlug])
            ->with('success', "Akun {$user->nama_user} ({$user->roleName()}) berhasil dibuat. Login memakai {$noInduk} atau nomor HP.");
    }

    /**
     * Ubah data akun. Akun nasabah hanya dapat direset kata sandinya.
     */
    public function update(Request $request, int $user): RedirectResponse
    {
        $akun = User::findOrFail($user);
        $lama = $this->jejak($akun);

        if ($akun->role === 'nasabah') {
            $data = $request->validate([
                'password' => ['required', 'string', 'min:6', 'max:64', 'confirmed'],
            ], $this->pesan());

            $akun->password = Hash::make($data['password']);
            $akun->save();

            AuditLogService::log(aksi: 'RESET_PASSWORD', entitas: 'tblUSER', entitasId: $akun->user_id, dataBaru: ['password_diubah' => true]);

            return redirect()
                ->route('akun.kelola', $this->paramKembali($request))
                ->with('success', "Kata sandi nasabah {$akun->nama_user} berhasil direset.");
        }

        $data = $this->validasi($request, $akun);

        $roleBaru = self::ROLE[$data['role']]['id'];
        if ($akun->user_id === auth()->id() && $roleBaru !== $akun->role_id) {
            throw ValidationException::withMessages(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
        }
        if ($akun->role === 'manager' && $roleBaru !== $akun->role_id) {
            $this->pastikanMasihAdaManagerAktif($akun);
        }

        $akun->fill([
            'role_id'   => $roleBaru,
            'nama_user' => $data['nama_user'],
            'no_induk'  => $data['no_induk'] ?: $akun->no_induk,
            'no_hp'     => $data['no_hp'],
            'email'     => $data['email'] ?: null,
        ]);
        $gantiSandi = !empty($data['password']);
        if ($gantiSandi) {
            $akun->password = Hash::make($data['password']);
        }
        $akun->save();

        AuditLogService::log(
            aksi: 'UPDATE',
            entitas: 'tblUSER',
            entitasId: $akun->user_id,
            dataLama: $lama,
            dataBaru: $this->jejak($akun) + ($gantiSandi ? ['password_diubah' => true] : [])
        );

        return redirect()
            ->route('akun.kelola', $this->paramKembali($request))
            ->with('success', "Data akun {$akun->nama_user} berhasil diperbarui.");
    }

    /**
     * Aktifkan / nonaktifkan akun. Akun nonaktif tidak dapat login dan sesi aktifnya diputus.
     */
    public function ubahStatus(Request $request, int $user): RedirectResponse
    {
        $akun = User::findOrFail($user);

        if ($akun->user_id === auth()->id()) {
            return redirect()->route('akun.kelola', $this->paramKembali($request))
                ->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $statusBaru = $akun->status_akun === 'aktif' ? 'nonaktif' : 'aktif';

        if ($statusBaru === 'nonaktif' && $akun->role === 'manager') {
            try {
                $this->pastikanMasihAdaManagerAktif($akun);
            } catch (ValidationException $e) {
                return redirect()->route('akun.kelola', $this->paramKembali($request))
                    ->with('error', $e->getMessage());
            }
        }

        $akun->status_akun = $statusBaru;
        $akun->save();

        AuditLogService::log(
            aksi: 'UPDATE_STATUS',
            entitas: 'tblUSER',
            entitasId: $akun->user_id,
            dataLama: ['status_akun' => $statusBaru === 'aktif' ? 'nonaktif' : 'aktif'],
            dataBaru: ['status_akun' => $statusBaru]
        );

        return redirect()
            ->route('akun.kelola', $this->paramKembali($request))
            ->with('success', "Akun {$akun->nama_user} sekarang " . ($statusBaru === 'aktif' ? 'aktif dan dapat login kembali.' : 'nonaktif dan tidak dapat login.'));
    }

    private function validasi(Request $request, ?User $akun = null): array
    {
        $request->merge([
            'nama_user' => strip_tags(trim((string) $request->input('nama_user', ''))),
            'no_induk'  => strtoupper(strip_tags(trim((string) $request->input('no_induk', '')))),
            'no_hp'     => preg_replace('/[^0-9+]/', '', (string) $request->input('no_hp', '')),
            'email'     => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $idAbaikan = $akun?->user_id;

        return $request->validate([
            'role'      => ['required', Rule::in(self::ROLE_PETUGAS)],
            'nama_user' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z\s\.,\'-]+$/'],
            // Siswa Praktik login dengan NISN (10 digit); petugas dengan kode pegawai (kosong = dibuat otomatis)
            'no_induk'  => [
                Rule::requiredIf($request->input('role') === 'siswa_praktik'),
                'nullable', 'string', 'max:30',
                $request->input('role') === 'siswa_praktik' ? 'regex:/^[0-9]{10}$/' : 'regex:/^[A-Z0-9\-\.\/]+$/',
                Rule::unique('tblUSER', 'no_induk')->ignore($idAbaikan, 'user_id'),
            ],
            'no_hp'     => ['required', 'string', 'min:10', 'max:15', 'regex:/^[0-9+]+$/', Rule::unique('tblUSER', 'no_hp')->ignore($idAbaikan, 'user_id')],
            'email'     => ['nullable', 'email', 'max:100', Rule::unique('tblUSER', 'email')->ignore($idAbaikan, 'user_id')],
            'password'  => [$akun ? 'nullable' : 'required', 'string', 'min:6', 'max:64', 'confirmed'],
        ], $this->pesan());
    }

    private function pesan(): array
    {
        return [
            'role.required'      => 'Peran pengguna wajib dipilih.',
            'role.in'            => 'Peran tidak valid. Akun nasabah dibuat melalui pendaftaran rekening.',
            'nama_user.required' => 'Nama lengkap wajib diisi.',
            'nama_user.min'      => 'Nama lengkap minimal 3 karakter.',
            'nama_user.regex'    => 'Nama hanya boleh berisi huruf, spasi, titik, koma, atau tanda petik.',
            'no_induk.required'  => 'NISN wajib diisi untuk akun Siswa Praktik.',
            'no_induk.regex'     => 'Format nomor induk tidak valid. Siswa Praktik memakai NISN 10 digit; petugas memakai kode seperti TLR-002.',
            'no_induk.unique'    => 'Nomor induk / kode pegawai ini sudah dipakai akun lain.',
            'no_hp.required'     => 'Nomor HP wajib diisi.',
            'no_hp.min'          => 'Nomor HP minimal 10 digit.',
            'no_hp.max'          => 'Nomor HP maksimal 15 digit.',
            'no_hp.regex'        => 'Nomor HP hanya boleh berisi angka.',
            'no_hp.unique'       => 'Nomor HP ini sudah dipakai akun lain.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email ini sudah dipakai akun lain.',
            'password.required'  => 'Kata sandi awal wajib diisi.',
            'password.min'       => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }

    /**
     * Kode pegawai berikutnya untuk prefix tertentu, mis. TLR-001 -> TLR-002.
     */
    private function kodeBerikutnya(string $prefix): string
    {
        $terbesar = DB::table('tblUSER')
            ->where('no_induk', 'like', $prefix . '%')
            ->pluck('no_induk')
            ->map(fn ($kode) => (int) preg_replace('/\D/', '', $kode))
            ->max() ?? 0;

        return sprintf('%s-%03d', $prefix, $terbesar + 1);
    }

    private function pastikanMasihAdaManagerAktif(User $akun): void
    {
        $managerAktifLain = User::where('role_id', self::ROLE['manager']['id'])
            ->where('status_akun', 'aktif')
            ->where('user_id', '!=', $akun->user_id)
            ->count();

        if ($managerAktifLain === 0) {
            throw ValidationException::withMessages([
                'role' => "{$akun->nama_user} adalah satu-satunya Manager aktif. Tambahkan Manager lain terlebih dahulu.",
            ]);
        }
    }

    /** Data akun untuk audit log (tanpa kata sandi). */
    private function jejak(User $akun): array
    {
        return [
            'role'        => $akun->role,
            'nama_user'   => $akun->nama_user,
            'no_induk'    => $akun->no_induk,
            'no_hp'       => $akun->no_hp,
            'email'       => $akun->email,
            'status_akun' => $akun->status_akun,
        ];
    }

    private function paramKembali(Request $request): array
    {
        return array_filter([
            'role'   => $request->input('kembali_role'),
            'status' => $request->input('kembali_status'),
            'cari'   => $request->input('kembali_cari'),
            'page'   => $request->input('kembali_page'),
        ]);
    }
}
