@extends('layouts.app')

@section('title', 'Pencairan Pinjaman Pegawai')
@section('breadcrumb', 'Pencairan Pinjaman')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Pencairan Pinjaman Pegawai</h1>
        <p>Eksekusi penyerahan dana pinjaman yang telah disetujui komite, pembentukan jadwal angsuran otomatis, dan pencatatan kas keluar.</p>
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

{{-- ── DAFTAR PINJAMAN SIAP DICAIRKAN ── --}}
<div class="content-card" style="margin-bottom: 24px;">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Pinjaman Berstatus Layak (Siap Dicairkan)</h2>
            <div style="font-size:12px; color:var(--text-muted);">Pencairan otomatis memotong saldo Kas Tunai hari ini dan membentuk jadwal tagihan cicilan</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="position:relative; width:260px;">
                <input type="text" class="form-input" placeholder="Cari nama atau ID pinjaman..." 
                       oninput="filterTableRows(this, 'tableSiapCair')"
                       style="padding:6px 12px; font-size:12.5px; border-radius:6px; width:100%;">
            </div>
            <span class="badge-pill green">{{ $siapCair->count() }} Siap Cair</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableSiapCair">
            <thead>
                <tr>
                    <th>ID Pinjaman</th>
                    <th>Nama Peminjam / Unit</th>
                    <th style="text-align: right;">Plafon Disetujui</th>
                    <th>Tenor</th>
                    <th>Metode Bayar</th>
                    <th>Catatan Kelayakan</th>
                    <th style="text-align: center;">Tindakan Pencairan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($siapCair as $p)
                    <tr>
                        <td>
                            <span style="font-weight:700; color:var(--text-heading); font-family:monospace;">
                                #PINJ-{{ str_pad($p->pinjaman_id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $p->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $p->kelas_unit ?? 'Pegawai' }}</div>
                        </td>
                        <td style="text-align: right; font-weight:800; color:var(--text-heading);">
                            Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}
                        </td>
                        <td>{{ $p->tenor }} Bulan</td>
                        <td>
                            <span class="badge-pill {{ $p->metode_bayar === 'potong_gaji' ? 'blue' : 'amber' }}">
                                {{ $p->metode_bayar === 'potong_gaji' ? 'Potong Gaji' : 'Tunai' }}
                            </span>
                        </td>
                        <td style="font-size:12px; color:var(--text-muted); max-width:240px;">
                            {{ $p->catatan_kelayakan }}
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-ui btn-ui-sm btn-ui-primary"
                                    onclick="bukaModalPencairan({{ $p->pinjaman_id }}, '{{ addslashes($p->nama_nasabah) }}', '{{ addslashes($p->kelas_unit ?? 'Pegawai') }}', {{ $p->nominal_pinjaman }}, {{ $p->tenor }}, '{{ $p->metode_bayar }}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Cairkan Dana
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-placeholder" style="padding:32px 16px; text-align:center;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 8px; color:var(--text-faint); display:block;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Saat ini tidak ada pinjaman berstatus Layak yang menunggu pencairan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── 10 RIWAYAT PENCAIRAN TERAKHIR ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <h2 class="card-heading">Riwayat Pencairan Pinjaman Terakhir</h2>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Tgl Pencairan</th>
                    <th>Nama Peminjam</th>
                    <th style="text-align: right;">Nominal Cair</th>
                    <th>Metode Pencairan</th>
                    <th>Tenor</th>
                    <th style="text-align: center;">Kartu Piutang</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatCair as $r)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($r->tgl_cair)->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $r->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $r->kelas_unit }}</div>
                        </td>
                        <td style="text-align: right; font-weight:700; color:#166534;">
                            Rp {{ number_format($r->nominal_cair, 0, ',', '.') }}
                        </td>
                        <td>
                            @if (($r->metode_cair ?? 'cash') === 'transfer')
                                <span class="badge-pill blue" style="font-size:11.5px;">Transfer (Bank)</span>
                            @else
                                <span class="badge-pill green" style="font-size:11.5px;">Tunai (Cash)</span>
                            @endif
                        </td>
                        <td>{{ $r->tenor }} Bulan</td>
                        <td style="text-align: center;">
                            <a href="{{ route('bbm.kartu.index', ['pinjaman_id' => $r->pinjaman_id]) }}" class="btn-ui btn-ui-sm btn-ui-outline" style="text-decoration:none;">
                                Lihat Kartu
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:24px 16px; text-align:center;">
                                Belum ada riwayat pencairan pinjaman.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── MODAL POP-UP: PROSES PENCAIRAN DANA (CASH / TRANSFER) ── --}}
<div id="modalPencairanDana" class="sipintar-modal-backdrop" style="display:none; z-index:9999; align-items:center; justify-content:center;" onclick="tutupModalPencairan(event)">
    <div class="sipintar-modal-card" style="max-width:520px; background:#FFFFFF; border-radius:14px; overflow:hidden; border:1px solid #E2E8F0; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" onclick="event.stopPropagation()">
        
        <div style="padding:18px 24px; background:#0D5C46; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:#A7F3D0; letter-spacing:0.04em;">Disbursement Pinjaman</div>
                <h3 style="margin:2px 0 0 0; font-size:16px; font-weight:800; color:#FFFFFF;">Formulir Pencairan Dana</h3>
            </div>
            <button type="button" onclick="tutupModalPencairan()" style="background:rgba(255,255,255,0.15); border:none; color:#FFFFFF; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:20px; display:flex; align-items:center; justify-content:center;">&times;</button>
        </div>

        <form id="formEksekusiPencairan" method="POST" action="" style="padding:22px 24px;">
            @csrf
            <input type="hidden" name="tgl_cair" value="{{ date('Y-m-d') }}">

            {{-- Ringkasan Peminjam & Nominal --}}
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:16px; margin-bottom:18px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.04em;">Penerima Dana</div>
                        <div style="font-weight:800; font-size:15px; color:#0F172A; margin-top:2px;" id="dispCairNama">-</div>
                        <div style="font-size:12px; color:var(--text-muted);" id="dispCairUnit">-</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.04em;">Nominal Cair</div>
                        <div style="font-weight:800; font-size:17px; color:#0D5C46; margin-top:2px;" id="dispCairNominal">Rp 0</div>
                        <div style="font-size:11.5px; color:var(--text-muted);" id="dispCairTenor">-</div>
                    </div>
                </div>
            </div>

            {{-- Pilihan Opsi Pencairan: Cash vs Transfer --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
                    Pilih Metode Penyerahan Dana: <span style="color:#DC2626;">*</span>
                </label>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <label style="border:2px solid #0D5C46; border-radius:10px; padding:12px 14px; cursor:pointer; background:#F0FDF4; display:flex; flex-direction:column; gap:4px; transition:all 0.2s;" id="labelMetodeCash">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="radio" name="metode_pencairan" value="cash" checked onchange="ubahPilihanMetode('cash')" style="accent-color:#0D5C46;">
                            <span style="font-weight:800; font-size:13.5px; color:#064E3B;">💵 Tunai (Cash)</span>
                        </div>
                        <div style="font-size:11px; color:#065F46; margin-left:24px;">
                            Dari Kas Brankas Loket<br>
                            <strong>Saldo: Rp {{ number_format($kasTunai->saldo ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </label>

                    <label style="border:1px solid #E2E8F0; border-radius:10px; padding:12px 14px; cursor:pointer; background:#FFFFFF; display:flex; flex-direction:column; gap:4px; transition:all 0.2s;" id="labelMetodeTransfer">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="radio" name="metode_pencairan" value="transfer" onchange="ubahPilihanMetode('transfer')" style="accent-color:#0D5C46;">
                            <span style="font-weight:800; font-size:13.5px; color:#0F172A;">💳 Transfer (Bank)</span>
                        </div>
                        <div style="font-size:11px; color:var(--text-muted); margin-left:24px;">
                            Dari Rekening Bank BJB<br>
                            <strong>Saldo: Rp {{ number_format($kasBank->saldo ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Catatan / Keterangan Tambahan --}}
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--text-heading); margin-bottom:6px;">
                    Catatan / Nomor Referensi Transaksi (Opsional):
                </label>
                <input type="text" name="catatan" class="form-input" placeholder="Contoh: No. Ref BJB / Diterima langsung oleh peminjam" style="font-size:13px; width:100%; border-radius:8px;">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #E2E8F0;">
                <button type="button" class="btn-ui btn-ui-outline" onclick="tutupModalPencairan()" style="padding:8px 18px;">
                    Batal
                </button>
                <button type="button" class="btn-ui btn-ui-primary" onclick="konfirmasiEksekusiCair()" style="padding:8px 20px; font-weight:800;">
                    Ya, Cairkan Dana Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let activePinjamanId = null;
let activeNamaNasabah = '';
let activeNominalStr = '';

function bukaModalPencairan(pinjamanId, nama, unit, nominal, tenor, metodeBayar) {
    activePinjamanId = pinjamanId;
    activeNamaNasabah = nama;
    activeNominalStr = 'Rp ' + Number(nominal).toLocaleString('id-ID');

    document.getElementById('formEksekusiPencairan').action = '/bbm/pinjaman/cair/' + pinjamanId;
    document.getElementById('dispCairNama').textContent = nama;
    document.getElementById('dispCairUnit').textContent = unit;
    document.getElementById('dispCairNominal').textContent = activeNominalStr;
    document.getElementById('dispCairTenor').textContent = 'Tenor ' + tenor + ' Bulan (' + (metodeBayar === 'potong_gaji' ? 'Potong Gaji' : 'Tunai Loket') + ')';

    // Default cash
    ubahPilihanMetode('cash');
    const modal = document.getElementById('modalPencairanDana');
    modal.style.display = 'flex';
}

function tutupModalPencairan(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('modalPencairanDana').style.display = 'none';
}

function ubahPilihanMetode(metode) {
    const labelCash = document.getElementById('labelMetodeCash');
    const labelTransfer = document.getElementById('labelMetodeTransfer');

    if (metode === 'cash') {
        labelCash.style.borderColor = '#0D5C46';
        labelCash.style.background = '#F0FDF4';
        labelTransfer.style.borderColor = '#E2E8F0';
        labelTransfer.style.background = '#FFFFFF';
        document.querySelector('input[name="metode_pencairan"][value="cash"]').checked = true;
    } else {
        labelTransfer.style.borderColor = '#0284C7';
        labelTransfer.style.background = '#F0F9FF';
        labelCash.style.borderColor = '#E2E8F0';
        labelCash.style.background = '#FFFFFF';
        document.querySelector('input[name="metode_pencairan"][value="transfer"]').checked = true;
    }
}

function konfirmasiEksekusiCair() {
    const metode = document.querySelector('input[name="metode_pencairan"]:checked').value;
    const labelMetode = (metode === 'transfer') ? 'Transfer Rekening Bank BJB' : 'Uang Tunai (Cash di Loket)';

    sipintarConfirm({
        title: 'Konfirmasi Pencairan Dana Pinjaman',
        message: `Cairkan pinjaman sebesar <strong>${activeNominalStr}</strong> kepada <strong>${activeNamaNasabah}</strong> melalui <strong>${labelMetode}</strong>?<br><br><span style="font-size:12px; color:#64748B;">Saldo kas terkait akan otomatis dipotong dan jadwal cicilan akan segera aktif.</span>`,
        confirmText: 'Ya, Serahkan Dana',
        cancelText: 'Periksa Kembali',
        onConfirm: function() {
            document.getElementById('formEksekusiPencairan').submit();
        }
    });
}
</script>

@endsection

