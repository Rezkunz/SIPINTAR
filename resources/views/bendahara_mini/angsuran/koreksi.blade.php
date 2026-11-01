@extends('layouts.app')

@section('title', 'Koreksi Transaksi Angsuran')
@section('breadcrumb', 'Koreksi Angsuran')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner" style="margin-bottom: 24px;">
    <div class="welcome-content">
        <h1>Koreksi & Pembatalan Transaksi Angsuran</h1>
        <p>Prosedur koreksi berbasis audit trail per paket pinjaman pegawai menggunakan mutasi pengeluaran kas pembalik tanpa menghapus histori data fisik.</p>
    </div>
</div>

@if (session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#166534; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#991b1b; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- ── TABEL 1: DAFTAR PAKET PINJAMAN DENGAN TRANSAKSI ANGSURAN TERBUKU ── --}}
<div class="content-card" style="margin-bottom: 24px;">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Daftar Paket Pinjaman Ber-Transaksi</h2>
            <div style="font-size:12px; color:var(--text-muted);">Pilih paket pinjaman untuk melihat rincian angsuran yang telah dibukukan dan memilih transaksi yang ingin dikoreksi</div>
        </div>
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:280px; position:relative;">
                <input type="text" class="form-input" placeholder="Cari nama peminjam, ID pinjaman..." 
                       oninput="filterTableRows(this, 'tablePaketKoreksi')"
                       style="padding:7px 14px; font-size:13px; border-radius:8px; width:100%;">
            </div>
            <span class="badge-pill green">{{ $paketPinjamanList->count() }} Paket</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tablePaketKoreksi">
            <thead>
                <tr>
                    <th style="min-width: 150px;">Paket Pinjaman</th>
                    <th style="min-width: 200px;">Peminjam & Unit</th>
                    <th style="text-align: center; min-width: 150px;">Transaksi Terbuku</th>
                    <th style="text-align: right; min-width: 160px;">Total Kas Masuk</th>
                    <th style="text-align: center; min-width: 160px;">Aksi Koreksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paketPinjamanList as $p)
                    <tr>
                        <td>
                            <div style="font-weight:800; font-family:monospace; color:var(--text-heading); font-size:13.5px;">
                                #PINJ-{{ str_pad($p->pinjaman_id, 4, '0', STR_PAD_LEFT) }}
                            </div>
                            <div style="font-size:12px; font-weight:700; color:#0D5C46; margin-top:2px;">
                                Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}
                            </div>
                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:1px;">
                                Tenor {{ $p->tenor }} Bulan &bull; {{ $p->metode_bayar === 'potong_gaji' ? 'Potong Payroll' : 'Tunai Kasir' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700; font-size:14px; color:var(--text-heading);">{{ $p->nama_nasabah }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">{{ $p->kelas_unit ?? 'Pegawai SMKN 1 Lemahabang' }}</div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-pill {{ $p->transaksi_aktif > 0 ? 'blue' : 'gray' }}" style="font-weight:700;">
                                {{ $p->transaksi_aktif }} Transaksi Sah
                            </span>
                            @if ($p->total_transaksi > $p->transaksi_aktif)
                                <div style="font-size:10.5px; color:#DC2626; margin-top:3px; font-weight:600;">
                                    {{ $p->total_transaksi - $p->transaksi_aktif }} Dibatalkan
                                </div>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="font-weight:800; font-size:14px; color:#0D5C46;">
                                Rp {{ number_format($p->total_dana_masuk, 0, ',', '.') }}
                            </div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:1px;">
                                Terbuku di Kas Bank Mini
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-ui btn-ui-sm btn-ui-primary" 
                                    onclick="bukaModalPilihTransaksi({{ $p->pinjaman_id }})"
                                    style="padding:6px 14px; font-weight:700;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                <span>Pilih Transaksi</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-placeholder" style="padding:36px; text-align:center;">
                                Belum ada transaksi angsuran yang dibukukan untuk dikoreksi.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── TABEL 2: RIWAYAT AUDIT KOREKSI / REVERSAL TRANSAKSI ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Riwayat Audit Transaksi Koreksi &amp; Pembatalan</h2>
            <div style="font-size:12px; color:var(--text-muted);">Rekam jejak mutasi pembalik kas yang telah dibukukan oleh petugas</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Tgl Koreksi</th>
                    <th>No. Angsuran</th>
                    <th>Peminjam & Unit</th>
                    <th style="text-align: right;">Nominal Dibalik</th>
                    <th>Alasan Koreksi / Berita Acara</th>
                    <th>Petugas Eksekutor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatKoreksi as $rk)
                    <tr>
                        <td style="font-size:13px;">{{ \Carbon\Carbon::parse($rk->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <div style="font-weight:700; font-family:monospace; color:var(--text-heading);">
                                #ANG-{{ str_pad($rk->angsuran_id, 4, '0', STR_PAD_LEFT) }}
                            </div>
                            <div style="font-size:11px; color:var(--text-muted);">
                                Cicilan Ke-{{ $rk->angsuran_ke }} (#PINJ-{{ $rk->pinjaman_id }})
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $rk->nama_nasabah }}</div>
                        </td>
                        <td style="text-align: right;">
                            <div style="font-weight:800; color:#DC2626;">
                                - Rp {{ number_format($rk->bayar_pokok + $rk->bayar_bunga, 0, ',', '.') }}
                            </div>
                            <div style="font-size:10.5px; color:var(--text-muted);">Kas Keluar Reversal</div>
                        </td>
                        <td>
                            <span style="font-size:12.5px; color:#991B1B; font-weight:600;">
                                {{ $rk->alasan_koreksi }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:700; font-size:12.5px; color:var(--text-heading);">{{ $rk->nama_eksekutor }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">Bendahara Bank Mini</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:28px; text-align:center;">
                                Belum pernah ada transaksi koreksi yang dilakukan. Seluruh pembukuan sah.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($riwayatKoreksi->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
            {{ $riwayatKoreksi->links() }}
        </div>
    @endif
</div>

{{-- ── MODAL 1: DAFTAR TRANSAKSI ANGSURAN PER PAKET PINJAMAN ── --}}
<div id="modalPilihTransaksi" class="sipintar-modal-backdrop" style="display:none; z-index:9999; align-items:center; justify-content:center;" onclick="tutupModalPilih(event)">
    <div class="sipintar-modal-card" style="max-width:640px; background:#FFFFFF; border-radius:14px; overflow:hidden; border:1px solid #E2E8F0;" onclick="event.stopPropagation()">
        <div style="padding:16px 22px; background:#0D5C46; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:800; color:#FFFFFF;" id="modalPilihTitle">Daftar Angsuran Terbuku</h3>
                <div style="font-size:11.5px; color:#A7F3D0; margin-top:2px;" id="modalPilihSubtitle">Pilih salah satu transaksi angsuran yang hendak dikoreksi</div>
            </div>
            <button type="button" onclick="tutupModalPilih()" style="background:rgba(255,255,255,0.15); border:none; color:#FFFFFF; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:18px;">&times;</button>
        </div>

        <div style="padding:20px; max-height:60vh; overflow-y:auto;" id="modalPilihBody">
            {{-- Injected dynamically via JS --}}
        </div>

        <div style="padding:12px 22px; background:#F8FAFC; border-top:1px solid #E2E8F0; text-align:right;">
            <button type="button" class="btn-ui btn-ui-outline" onclick="tutupModalPilih()">Tutup</button>
        </div>
    </div>
</div>

{{-- ── MODAL 2: FORM INPUT ALASAN KOREKSI / BERITA ACARA ── --}}
<div id="modalFormKoreksi" class="sipintar-modal-backdrop" style="display:none; z-index:10000; align-items:center; justify-content:center;" onclick="tutupModalForm(event)">
    <div class="sipintar-modal-card" style="max-width:480px; background:#FFFFFF; border-radius:14px; overflow:hidden; border:1px solid #E2E8F0; box-shadow:0 25px 50px -12px rgba(15,23,42,0.3);" onclick="event.stopPropagation()">
        
        <div style="padding:18px 22px; background:#DC2626; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#FECACA; letter-spacing:0.04em;">Audit Trail Pembukuan</div>
                <h3 style="margin:2px 0 0 0; font-size:16px; font-weight:800; color:#FFFFFF;">Form Koreksi Transaksi Angsuran</h3>
            </div>
            <button type="button" onclick="tutupModalForm()" style="background:rgba(255,255,255,0.2); border:none; color:#FFFFFF; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:18px;">&times;</button>
        </div>

        <form id="formKoreksiAction" method="POST" action="" style="padding:22px;">
            @csrf

            {{-- Ringkasan Item yang akan dikoreksi --}}
            <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
                <div style="font-weight:800; font-size:14.5px; color:#991B1B;" id="formKoreksiNama">Nama Peminjam</div>
                <div style="font-size:12.5px; color:#B91C1C; margin-top:3px;" id="formKoreksiDetail">Angsuran Ke-N &bull; Total Rp 0</div>
            </div>

            <div style="font-size:12px; color:#64748B; line-height:1.45; margin-bottom:16px;">
                <strong>Perhatian:</strong> Koreksi ini akan mengembalikan status tagihan jadwal angsuran menjadi <em>belum bayar</em>, mencatat mutasi pengeluaran kas pembalik, dan mengurangi saldo kas operasional.
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" for="alasan_koreksi" style="display:block; font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:6px;">
                    Alasan Pembatalan / Koreksi <span style="color:#DC2626;">*</span>
                </label>
                <textarea name="alasan_koreksi" id="alasan_koreksi" class="form-textarea" rows="3" required
                          placeholder="Contoh: Salah pilih nama nasabah penyetor atau salah input nominal"
                          style="width:100%; padding:10px 12px; font-size:13px; border:1px solid #CBD5E1; border-radius:8px; box-sizing:border-box;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-ui btn-ui-outline" onclick="tutupModalForm()">Batal</button>
                <button type="button" class="btn-ui btn-ui-danger" onclick="konfirmasiSubmitKoreksi()" style="background:#DC2626; border-color:#DC2626; font-weight:700;">
                    Konfirmasi Koreksi
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $paketJson = [];
    foreach ($paketPinjamanList as $p) {
        $paketJson[$p->pinjaman_id] = [
            'pinjaman_id' => $p->pinjaman_id,
            'nama' => $p->nama_nasabah,
            'unit' => $p->kelas_unit,
            'nominal' => $p->nominal_pinjaman,
            'tenor' => $p->tenor,
            'transaksi' => $p->daftar_transaksi->map(function($t) {
                return [
                    'angsuran_id' => $t->angsuran_id,
                    'angsuran_ke' => $t->angsuran_ke,
                    'tgl_bayar' => \Carbon\Carbon::parse($t->tgl_bayar)->format('d/m/Y'),
                    'pokok' => $t->bayar_pokok,
                    'bunga' => $t->bayar_bunga,
                    'total' => $t->bayar_pokok + $t->bayar_bunga,
                    'is_koreksi' => !empty($t->koreksi_id),
                    'alasan_koreksi' => $t->alasan_koreksi ?? '',
                ];
            })
        ];
    }
@endphp

<script>
const PAKET_DATA = @json($paketJson);

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function bukaModalPilihTransaksi(pinjamanId) {
    const data = PAKET_DATA[pinjamanId];
    if (!data) return;

    document.getElementById('modalPilihTitle').innerText = `Angsuran: ${data.nama}`;
    document.getElementById('modalPilihSubtitle').innerText = `#PINJ-${String(data.pinjaman_id).padStart(4, '0')} • Plafon: ${formatRupiah(data.nominal)} (${data.tenor} Bulan)`;

    let html = `
        <table class="financial-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th>Ke</th>
                    <th>Tgl Bayar</th>
                    <th style="text-align:right;">Total Kas</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
    `;

    if (!data.transaksi || data.transaksi.length === 0) {
        html += `<tr><td colspan="5" style="text-align:center; padding:20px; color:#94A3B8;">Belum ada angsuran yang dibukukan.</td></tr>`;
    } else {
        data.transaksi.forEach(t => {
            html += `
                <tr style="${t.is_koreksi ? 'background:#FEF2F2;' : ''}">
                    <td style="font-weight:700;">Ke-${t.angsuran_ke}</td>
                    <td>${t.tgl_bayar}</td>
                    <td style="text-align:right; font-weight:800; color:${t.is_koreksi ? '#991B1B' : '#0D5C46'};">
                        ${formatRupiah(t.total)}
                    </td>
                    <td style="text-align:center;">
                        <span class="badge-pill ${t.is_koreksi ? 'red' : 'green'}" style="font-size:11px;">
                            ${t.is_koreksi ? 'Telah Dibatalkan' : 'Sah (Aktif)'}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        ${t.is_koreksi 
                            ? `<span style="font-size:11px; color:#94A3B8;">-</span>` 
                            : `<button type="button" class="btn-ui btn-ui-sm btn-ui-danger" style="padding:4px 10px; font-size:11.5px;" 
                                      onclick="siapkanFormKoreksi(${t.angsuran_id}, '${escapeHtml(data.nama)}', ${t.angsuran_ke}, ${t.total})">
                                   Koreksi
                               </button>`
                        }
                    </td>
                </tr>
            `;
        });
    }

    html += `</tbody></table>`;
    document.getElementById('modalPilihBody').innerHTML = html;

    const modal = document.getElementById('modalPilihTransaksi');
    modal.style.display = 'flex';
    modal.classList.add('open');
}

function escapeHtml(text) {
    return text.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function tutupModalPilih(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('modalPilihTransaksi');
    modal.style.display = 'none';
    modal.classList.remove('open');
}

function siapkanFormKoreksi(angsuranId, nama, angsuranKe, total) {
    // Tutup modal pemilih dulu
    const mPilih = document.getElementById('modalPilihTransaksi');
    mPilih.style.display = 'none';
    mPilih.classList.remove('open');

    // Buka modal form koreksi
    const form = document.getElementById('formKoreksiAction');
    form.action = `/bbm/angsuran/koreksi/${angsuranId}`;

    document.getElementById('formKoreksiNama').innerText = nama;
    document.getElementById('formKoreksiDetail').innerText = `Angsuran Ke-${angsuranKe} • Total Nominal: ${formatRupiah(total)}`;
    document.getElementById('alasan_koreksi').value = '';

    const modal = document.getElementById('modalFormKoreksi');
    modal.style.display = 'flex';
    modal.classList.add('open');
}

function tutupModalForm(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('modalFormKoreksi');
    modal.style.display = 'none';
    modal.classList.remove('open');
}

function konfirmasiSubmitKoreksi() {
    const alasan = document.getElementById('alasan_koreksi').value.trim();
    if (alasan.length < 5) {
        alert('Mohon cantumkan alasan koreksi minimal 5 karakter sebagai bukti audit.');
        return;
    }

    sipintarConfirm({
        title: 'Batalkan & Koreksi Transaksi Angsuran?',
        message: 'Tindakan ini akan membalik pembukuan kas dan mengembalikan status cicilan ini menjadi belum bayar. Lanjutkan?',
        confirmText: 'Ya, Lakukan Koreksi',
        cancelText: 'Batal',
        confirmColor: '#DC2626',
        onConfirm: () => {
            document.getElementById('formKoreksiAction').submit();
        }
    });
}
</script>

@endsection
