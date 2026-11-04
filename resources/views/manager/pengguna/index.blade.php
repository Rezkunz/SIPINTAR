@extends('layouts.app')

@section('title', 'Kelola Pengguna')
@section('breadcrumb', 'Kelola Pengguna')

@section('content')

@php
    $role = \App\Http\Controllers\Manager\KelolaPenggunaController::ROLE;
    $rolePetugas = \App\Http\Controllers\Manager\KelolaPenggunaController::ROLE_PETUGAS;
    $isUbah = (bool) $diubah;
    $ubahNasabah = $isUbah && $diubah->role === 'nasabah';
    $nilaiForm = fn ($kolom, $default = '') => old($kolom, $isUbah ? ($diubah->{$kolom} ?? $default) : $default);
    $roleTerpilih = old('role', $isUbah ? $diubah->role : 'teller');
    $badgeRole = [
        'manager' => 'green', 'bendahara_mini' => 'blue', 'bendahara' => 'blue',
        'teller' => 'amber', 'siswa_praktik' => 'amber', 'nasabah' => '',
    ];
    // Filter aktif ikut dibawa saat form dikirim agar kembali ke tampilan yang sama
    $paramFilter = array_filter(['role' => $filterRole, 'status' => $filterStatus, 'cari' => $cari]);
    $hiddenKembali = function () use ($filterRole, $filterStatus, $cari, $pengguna) {
        return collect(['kembali_role' => $filterRole, 'kembali_status' => $filterStatus, 'kembali_cari' => $cari, 'kembali_page' => $pengguna->currentPage() > 1 ? $pengguna->currentPage() : null])
            ->filter()
            ->map(fn ($v, $k) => '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">')
            ->implode('');
    };
@endphp

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Kelola Pengguna</h1>
        <p>Buat dan atur akun petugas Bank Mini (Teller, Bendahara, Bendahara Bank Mini, Manager, Siswa Praktik), reset kata sandi, serta aktifkan atau nonaktifkan akses login. Akun nasabah terbentuk otomatis saat pendaftaran rekening.</p>
    </div>
</div>

@include('manager.partials.notifikasi', ['judulGalat' => 'Akun belum tersimpan:'])

{{-- ── METRIK ── --}}
<div class="metric-grid" style="margin-bottom: 24px;">
    <div class="metric-card card-emerald">
        <div class="metric-header"><span class="metric-title">Akun Aktif</span></div>
        <div class="metric-value">{{ $totalAktif }}</div>
        <div class="mgr-metric-ket">Dapat login ke SIPINTAR</div>
    </div>
    <div class="metric-card card-amber">
        <div class="metric-header"><span class="metric-title">Akun Nonaktif</span></div>
        <div class="metric-value">{{ $totalNonaktif }}</div>
        <div class="mgr-metric-ket">Akses login diblokir</div>
    </div>
    <div class="metric-card card-blue">
        <div class="metric-header"><span class="metric-title">Petugas</span></div>
        <div class="metric-value">{{ $totalPetugas }}</div>
        <div class="mgr-metric-ket">Manager, Bendahara, Teller, Siswa Praktik</div>
    </div>
    <div class="metric-card card-purple">
        <div class="metric-header"><span class="metric-title">Nasabah</span></div>
        <div class="metric-value">{{ $jumlahPerRole['nasabah'] }}</div>
        <div class="mgr-metric-ket">Siswa dan guru/karyawan</div>
    </div>
</div>

{{-- ── FORM TAMBAH / UBAH ── --}}
<div class="content-card" id="form-akun">
    <div class="card-header-bar">
        <div>
            <h2 class="card-heading">{{ $isUbah ? ($ubahNasabah ? 'Reset Kata Sandi Nasabah' : 'Ubah Akun') : 'Tambah Akun Petugas' }}</h2>
            <div style="font-size:12px; color:var(--text-muted);">
                @if ($ubahNasabah)
                    Identitas nasabah dikelola lewat data rekening. Di sini Manager hanya dapat mengganti kata sandi login.
                @elseif ($isUbah)
                    Kosongkan kata sandi bila tidak ingin menggantinya.
                @else
                    Kode pegawai dibuat otomatis bila dikosongkan (format PRD: TLR-001, BND-001, BBM-001, MGR-001). Siswa Praktik login dengan NISN.
                @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ $isUbah ? route('akun.kelola.update', $diubah->user_id) : route('akun.kelola.store') }}" class="pos-form">
        @csrf
        @if ($isUbah)
            @method('PUT')
        @endif
        {!! $hiddenKembali() !!}

        <div class="pos-form-title">
            {{ $isUbah ? $diubah->nama_user . ' · ' . $diubah->roleName() : 'Data Akun Baru' }}
        </div>

        @if ($ubahNasabah)
            <div class="pengguna-form-grid">
                <div class="lap-field">
                    <span class="lap-field-label">Nama Nasabah</span>
                    <input type="text" class="pos-input" value="{{ $diubah->nama_user }}" disabled>
                </div>
                <div class="lap-field">
                    <span class="lap-field-label">NISN / NIP &middot; No HP</span>
                    <input type="text" class="pos-input" value="{{ $diubah->no_induk ?? '-' }} · {{ $diubah->no_hp }}" disabled>
                </div>
                <div class="lap-field">
                    <label class="lap-field-label" for="akun_password">Kata Sandi Baru</label>
                    <input type="password" name="password" id="akun_password" class="pos-input" minlength="6" maxlength="64" autocomplete="new-password" required>
                </div>
                <div class="lap-field">
                    <label class="lap-field-label" for="akun_password2">Ulangi Kata Sandi</label>
                    <input type="password" name="password_confirmation" id="akun_password2" class="pos-input" minlength="6" maxlength="64" autocomplete="new-password" required>
                </div>
            </div>
        @else
            <div class="pengguna-form-grid">
                <div class="lap-field">
                    <label class="lap-field-label" for="akun_role">Peran</label>
                    <select name="role" id="akun_role" class="lap-select" style="height:38px;" required
                            {{ $isUbah && $diubah->user_id === auth()->id() ? 'disabled' : '' }}>
                        @foreach ($rolePetugas as $slug)
                            <option value="{{ $slug }}" data-saran="{{ $saranKode[$slug] ?? '' }}" {{ $roleTerpilih === $slug ? 'selected' : '' }}>{{ $role[$slug]['label'] }}</option>
                        @endforeach
                    </select>
                    @if ($isUbah && $diubah->user_id === auth()->id())
                        <input type="hidden" name="role" value="{{ $diubah->role }}">
                    @endif
                </div>

                <div class="lap-field pengguna-form-lebar">
                    <label class="lap-field-label" for="akun_nama">Nama Lengkap</label>
                    <input type="text" name="nama_user" id="akun_nama" class="pos-input" maxlength="100" required
                           value="{{ $nilaiForm('nama_user') }}" placeholder="mis. Siti Rahmawati">
                </div>

                <div class="lap-field">
                    <label class="lap-field-label" for="akun_induk" id="akun_induk_label">
                        {{ $roleTerpilih === 'siswa_praktik' ? 'NISN' : 'Kode Pegawai' }}
                    </label>
                    <input type="text" name="no_induk" id="akun_induk" class="pos-input" maxlength="30"
                           value="{{ $nilaiForm('no_induk') }}"
                           placeholder="{{ $roleTerpilih === 'siswa_praktik' ? '10 digit NISN' : ($isUbah ? '' : 'Otomatis: ' . ($saranKode[$roleTerpilih] ?? '')) }}">
                </div>

                <div class="lap-field">
                    <label class="lap-field-label" for="akun_hp">No HP / WhatsApp</label>
                    <input type="text" name="no_hp" id="akun_hp" class="pos-input" maxlength="15" inputmode="numeric" required
                           value="{{ $nilaiForm('no_hp') }}" placeholder="08xxxxxxxxxx">
                </div>

                <div class="lap-field">
                    <label class="lap-field-label" for="akun_email">Email (opsional)</label>
                    <input type="email" name="email" id="akun_email" class="pos-input" maxlength="100"
                           value="{{ $nilaiForm('email') }}" placeholder="nama@sipintar.sch.id">
                </div>

                <div class="lap-field">
                    <label class="lap-field-label" for="akun_password">{{ $isUbah ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi Awal' }}</label>
                    <input type="password" name="password" id="akun_password" class="pos-input" minlength="6" maxlength="64" autocomplete="new-password" {{ $isUbah ? '' : 'required' }}>
                </div>

                <div class="lap-field">
                    <label class="lap-field-label" for="akun_password2">Ulangi Kata Sandi</label>
                    <input type="password" name="password_confirmation" id="akun_password2" class="pos-input" minlength="6" maxlength="64" autocomplete="new-password" {{ $isUbah ? '' : 'required' }}>
                </div>
            </div>
            <div class="pos-form-hint">Kata sandi minimal 6 karakter. Sampaikan kata sandi awal kepada pemilik akun dan minta ia menggantinya lewat menu Profil.</div>
        @endif

        <div class="pos-form-actions">
            @if ($isUbah)
                <a href="{{ route('akun.kelola', $paramFilter) }}" class="btn-ui btn-ui-outline" style="text-decoration:none;">Batal</a>
            @endif
            <button type="submit" class="btn-ui btn-ui-primary" style="font-weight:700;">
                @if ($isUbah)
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    <span>{{ $ubahNasabah ? 'Reset Kata Sandi' : 'Simpan Perubahan' }}</span>
                @else
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Tambah Akun</span>
                @endif
            </button>
        </div>
    </form>
</div>

{{-- ── DAFTAR PENGGUNA ── --}}
<nav class="lap-tabs" aria-label="Saring Peran">
    <a href="{{ route('akun.kelola', array_filter(['status' => $filterStatus, 'cari' => $cari])) }}" class="lap-tab {{ !$filterRole ? 'active' : '' }}">
        Semua <span class="pengguna-tab-jumlah">{{ $jumlahPerRole->sum() }}</span>
    </a>
    @foreach ($role as $slug => $r)
        <a href="{{ route('akun.kelola', array_filter(['role' => $slug, 'status' => $filterStatus, 'cari' => $cari])) }}" class="lap-tab {{ $filterRole === $slug ? 'active' : '' }}">
            {{ $r['label'] }} <span class="pengguna-tab-jumlah">{{ $jumlahPerRole[$slug] }}</span>
        </a>
    @endforeach
</nav>

<form method="GET" action="{{ route('akun.kelola') }}" class="lap-toolbar">
    @if ($filterRole)
        <input type="hidden" name="role" value="{{ $filterRole }}">
    @endif
    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Status Akun
        </span>
        <div class="lap-segmented" role="radiogroup" aria-label="Status Akun">
            <input type="radio" name="status" id="status-semua" value="" {{ !$filterStatus ? 'checked' : '' }} onchange="this.form.submit()">
            <label for="status-semua">Semua</label>
            <input type="radio" name="status" id="status-aktif" value="aktif" {{ $filterStatus === 'aktif' ? 'checked' : '' }} onchange="this.form.submit()">
            <label for="status-aktif">Aktif</label>
            <input type="radio" name="status" id="status-nonaktif" value="nonaktif" {{ $filterStatus === 'nonaktif' ? 'checked' : '' }} onchange="this.form.submit()">
            <label for="status-nonaktif">Nonaktif</label>
        </div>
    </div>

    <div class="lap-field">
        <span class="lap-field-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Cari Pengguna
        </span>
        <div class="lap-search">
            <input type="search" name="cari" value="{{ $cari }}" placeholder="Nama / kode / NISN / HP / email...">
            <button type="submit" title="Cari">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </div>
    </div>
</form>

<div class="content-card">
    <div class="table-responsive">
        <table class="financial-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th style="min-width:200px;">Nama</th>
                    <th style="width:150px;">Peran</th>
                    <th style="min-width:120px;">Kode / NISN</th>
                    <th style="min-width:170px;">Kontak</th>
                    <th style="width:90px;">Status</th>
                    <th style="width:190px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengguna as $u)
                    @php $diriSendiri = $u->user_id === auth()->id(); @endphp
                    <tr style="{{ $isUbah && $diubah->user_id === $u->user_id ? 'background:var(--brand-accent-light);' : '' }}{{ $u->status_akun === 'nonaktif' ? 'opacity:.7;' : '' }}">
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">
                                {{ $u->nama_user }}
                                @if ($diriSendiri)
                                    <span style="font-size:10.5px; font-weight:700; color:var(--brand-primary);">(Anda)</span>
                                @endif
                            </div>
                            @if ($u->role === 'nasabah' && $u->nasabah)
                                <div style="font-size:11px; color:var(--text-muted);">
                                    {{ $u->nasabah->jenis_nasabah === 'pegawai' ? 'Guru/Karyawan' : 'Siswa' }}{{ $u->nasabah->kelas_unit ? ' · ' . $u->nasabah->kelas_unit : '' }}
                                </div>
                            @endif
                        </td>
                        <td><span class="badge-pill {{ $badgeRole[$u->role] ?? '' }}" style="font-size:10.5px; padding:2px 8px;">{{ $u->roleName() }}</span></td>
                        <td style="font-family:var(--font-mono, monospace); white-space:nowrap;">{{ $u->no_induk ?? '-' }}</td>
                        <td>
                            <div style="white-space:nowrap;">{{ $u->no_hp }}</div>
                            @if ($u->email)
                                <div style="font-size:11px; color:var(--text-muted);">{{ $u->email }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge-pill {{ $u->status_akun === 'aktif' ? 'green' : 'red' }}" style="font-size:10.5px; padding:2px 8px;">
                                {{ $u->status_akun === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="white-space:nowrap; text-align:right;">
                            <a href="{{ route('akun.kelola', array_merge($paramFilter, ['edit' => $u->user_id, 'page' => $pengguna->currentPage() > 1 ? $pengguna->currentPage() : null])) }}#form-akun"
                               class="btn-ui btn-ui-sm btn-ui-outline" style="font-size:11.5px; padding:4px 10px; text-decoration:none;">
                                {{ $u->role === 'nasabah' ? 'Reset Sandi' : 'Ubah' }}
                            </a>
                            @unless ($diriSendiri)
                                <form method="POST" action="{{ route('akun.kelola.status', $u->user_id) }}" style="display:inline;"
                                      onsubmit="return confirm(this.dataset.konfirmasi);"
                                      data-konfirmasi="{{ $u->status_akun === 'aktif' ? 'Nonaktifkan' : 'Aktifkan kembali' }} akun {{ $u->nama_user }}?">
                                    @csrf
                                    @method('PATCH')
                                    {!! $hiddenKembali() !!}
                                    @if ($u->status_akun === 'aktif')
                                        <button type="submit" class="btn-ui btn-ui-sm btn-ui-outline" style="font-size:11.5px; padding:4px 10px; color:#DC2626; border-color:#FECACA;">Nonaktifkan</button>
                                    @else
                                        <button type="submit" class="btn-ui btn-ui-sm btn-ui-outline" style="font-size:11.5px; padding:4px 10px; color:#047857; border-color:#A7F3D0;">Aktifkan</button>
                                    @endif
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:28px; text-align:center;">Tidak ada pengguna yang cocok dengan filter.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($pengguna->hasPages())
        <div style="padding:14px 20px;">{{ $pengguna->links() }}</div>
    @endif
</div>

@unless ($ubahNasabah)
<script>
    // Ubah label & petunjuk nomor induk sesuai peran yang dipilih
    (function () {
        const role = document.getElementById('akun_role');
        const induk = document.getElementById('akun_induk');
        const label = document.getElementById('akun_induk_label');
        if (!role || !induk || !label) return;
        const ubah = {{ $isUbah ? 'true' : 'false' }};
        role.addEventListener('change', function () {
            const siswa = role.value === 'siswa_praktik';
            label.textContent = siswa ? 'NISN' : 'Kode Pegawai';
            const saran = role.options[role.selectedIndex].dataset.saran;
            induk.placeholder = siswa ? '10 digit NISN' : (ubah ? '' : 'Otomatis: ' + saran);
        });
    })();
</script>
@endunless

@endsection
