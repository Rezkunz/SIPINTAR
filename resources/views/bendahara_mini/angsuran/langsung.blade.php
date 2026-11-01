@extends('layouts.app')

@section('title', 'Penerimaan Angsuran Langsung')
@section('breadcrumb', 'Angsuran Langsung')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Penerimaan Angsuran Langsung (Tunai)</h1>
        <p>Pencatatan pembayaran kasir angsuran di loket per paket pinjaman pegawai dengan hitungan paten dan pembaruan otomatis saldo kas.</p>
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

{{-- ── TABEL UTAMA: PAKET PINJAMAN AKTIF PEGAWAI (FULL WIDTH) ── --}}
<div class="content-card" style="margin-bottom: 24px;">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Daftar Paket Pinjaman Aktif Pegawai</h2>
            <div style="font-size:12px; color:var(--text-muted);">Pilih paket pinjaman untuk membuka formulir penerimaan kasir tunai</div>
        </div>
        
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="position:relative; width:280px;">
                <input type="text" class="form-input" placeholder="Cari nama peminjam, ID pinjaman, unit..." 
                       oninput="filterTableRows(this, 'tablePinjamanLangsung')"
                       style="padding:7px 14px; font-size:13px; border-radius:8px; width:100%;">
            </div>
            <span class="badge-pill green">{{ $pinjamanAktifList->count() }} Paket Berjalan</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tablePinjamanLangsung">
            <thead>
                <tr>
                    <th style="min-width: 140px;">Paket Pinjaman</th>
                    <th style="min-width: 180px;">Peminjam & Unit Kerja</th>
                    <th style="min-width: 150px;">Kemajuan Angsuran</th>
                    <th style="text-align: right; min-width: 160px;">Tagihan Terdekat</th>
                    <th style="text-align: center; min-width: 180px;">Tindakan Kasir</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pinjamanAktifList as $p)
                    @php
                        $tagihan = $p->tagihanBerikutnya;
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:800; color:var(--text-heading); font-family:monospace; font-size:13.5px;">
                                #PINJ-{{ str_pad($p->pinjaman_id, 4, '0', STR_PAD_LEFT) }}
                            </div>
                            <div style="font-size:12.5px; font-weight:700; color:#0D5C46; margin-top:2px;">
                                Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}
                            </div>
                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:1px;">
                                Tenor {{ $p->tenor }} Bulan • {{ $p->metode_bayar === 'potong_gaji' ? 'Potong Gaji' : 'Tunai Loket' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700; font-size:14px; color:var(--text-heading);">{{ $p->nama_nasabah }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                {{ $p->kelas_unit ?? 'Pegawai' }}
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="display:inline-block; font-size:12px; font-weight:700; padding:3px 10px; border-radius:6px; background:#DBEAFE; color:#1E40AF; border:1px solid #BFDBFE;">
                                    {{ $p->sudahBayar }} dari {{ $p->tenor }} Lunas
                                </span>
                            </div>
                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">
                                Sisa {{ $p->sisaBelumBayar }} bulan kewajiban
                            </div>
                        </td>
                        <td style="text-align: right;">
                            @if ($tagihan)
                                <div style="font-weight:800; color:#0284c7; font-size:15px;">
                                    Rp {{ number_format($tagihan->pokok + $tagihan->bunga, 0, ',', '.') }}
                                </div>
                                <div style="font-size:12px; font-weight:700; color:var(--text-heading); margin-top:1px;">
                                    Angsuran Ke-{{ $tagihan->angsuran_ke }}
                                </div>
                                <div style="font-size:11px; color:var(--text-muted);">
                                    Jatuh Tempo: {{ \Carbon\Carbon::parse($tagihan->jatuh_tempo)->format('d/m/Y') }}
                                </div>
                            @else
                                <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:6px; background:#DCFCE7; color:#166534; border:1px solid #BBF7D0;">
                                    Lunas Seluruhnya
                                </span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($tagihan)
                                <div style="display:inline-flex; gap:8px; align-items:center;">
                                    <button type="button" class="btn-ui btn-ui-sm btn-ui-primary" style="padding:6px 14px; font-weight:700;"
                                            onclick="bukaModalBayar({{ $tagihan->jadwal_angsuran_id }}, '{{ addslashes($p->nama_nasabah) }}', '{{ addslashes($p->kelas_unit ?? 'Pegawai') }}', {{ $p->pinjaman_id }}, {{ $tagihan->angsuran_ke }}, {{ $p->tenor }}, {{ $tagihan->pokok }}, {{ $tagihan->bunga }}, '{{ \Carbon\Carbon::parse($tagihan->jatuh_tempo)->format('d/m/Y') }}')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                                        Bayar Ke-{{ $tagihan->angsuran_ke }}
                                    </button>
                                    <button type="button" class="btn-ui btn-ui-sm btn-ui-outline" style="padding:6px 12px; font-weight:600;"
                                            onclick="bukaRincianPaket({{ $p->pinjaman_id }})">
                                        Lihat Jadwal
                                    </button>
                                </div>
                            @else
                                <span style="font-size:12px; color:#166534; font-weight:700;">Semua Angsuran Lunas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-placeholder" style="padding:36px 16px; text-align:center;">
                                Tidak ada pinjaman pegawai aktif yang sedang berjalan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── TABEL RIWAYAT TRANSAKSI TERAKHIR (PAGINASI 10 BARIS) ── --}}
<div class="content-card">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Riwayat Transaksi Penerimaan Angsuran Tunai</h2>
            <div style="font-size:12px; color:var(--text-muted);">Histori pembayaran langsung di loket teller (maksimal 10 baris per halaman)</div>
        </div>
        <div style="width:240px;">
            <input type="text" class="form-input" placeholder="Cari nama peminjam..." 
                   oninput="filterTableRows(this, 'tableRiwayatAngsuran')"
                   style="padding:6px 12px; font-size:12.5px; border-radius:6px; width:100%;">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="financial-table" id="tableRiwayatAngsuran">
            <thead>
                <tr>
                    <th>Tgl Bayar</th>
                    <th>Nama Peminjam / Unit</th>
                    <th>Angsuran Ke</th>
                    <th style="text-align: right;">Pokok</th>
                    <th style="text-align: right;">Jasa 2%</th>
                    <th style="text-align: right;">Total Kas Diterima</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatLangsung as $r)
                    <tr>
                        <td style="font-size:13px;">{{ \Carbon\Carbon::parse($r->tgl_bayar)->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $r->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $r->kelas_unit ?? 'Pegawai' }}</div>
                        </td>
                        <td>
                            <span class="badge-pill blue">Ke-{{ $r->angsuran_ke }}</span>
                        </td>
                        <td style="text-align: right;">Rp {{ number_format($r->bayar_pokok, 0, ',', '.') }}</td>
                        <td style="text-align: right;">Rp {{ number_format($r->bayar_bunga, 0, ',', '.') }}</td>
                        <td style="text-align: right; font-weight:800; color:#0D5C46;">
                            Rp {{ number_format($r->bayar_pokok + $r->bayar_bunga, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:28px; text-align:center;">
                                Belum ada riwayat penerimaan kasir.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($riwayatLangsung->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle);">
            {{ $riwayatLangsung->links() }}
        </div>
    @endif
</div>

{{-- ── MODAL POP-UP: FORM PEMBAYARAN KASIR (TUNAI) ── --}}
<div id="modalBayarKasir" class="sipintar-modal-backdrop" style="display:none; z-index:9999; align-items:center; justify-content:center;" onclick="tutupModalBayar(event)">
    <div class="sipintar-modal-card" style="max-width:520px; background:#FFFFFF; border-radius:14px; overflow:hidden; border:1px solid #E2E8F0; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" onclick="event.stopPropagation()">
        
        {{-- Header Modal --}}
        <div style="padding:18px 24px; background:#0D5C46; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:#A7F3D0; letter-spacing:0.04em;">Loket Kasir Bank Mini</div>
                <h3 style="margin:2px 0 0 0; font-size:16px; font-weight:800; color:#FFFFFF;">Form Penerimaan Angsuran Tunai</h3>
            </div>
            <button type="button" onclick="tutupModalBayar()" style="background:rgba(255,255,255,0.15); border:none; color:#FFFFFF; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:20px; display:flex; align-items:center; justify-content:center;">&times;</button>
        </div>

        {{-- Form Body --}}
        <form id="formBayarTunai" method="POST" action="{{ route('bbm.angsuran.langsung.simpan') }}" style="padding:22px 24px;">
            @csrf
            <input type="hidden" name="jadwal_angsuran_id" id="form_jadwal_id" required>
            <input type="hidden" name="tgl_bayar" id="form_tgl_bayar" value="{{ date('Y-m-d') }}" required>
            <input type="hidden" name="bayar_pokok" id="form_bayar_pokok" required>
            <input type="hidden" name="bayar_bunga" id="form_bayar_bunga" required>

            {{-- Ringkasan Peminjam --}}
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
                <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.04em;">Peminjam</div>
                <div style="font-weight:800; font-size:15px; color:#0F172A; margin-top:2px;" id="dispModalNama">-</div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:1px;" id="dispModalUnit">-</div>
                <div style="font-size:12px; font-weight:700; color:#0284c7; margin-top:8px; padding-top:8px; border-top:1px dashed #CBD5E1;" id="dispModalPaket">-</div>
            </div>

            {{-- Breakdown Hitungan Paten --}}
            <div style="display:flex; flex-direction:column; gap:10px; font-size:13px; margin-bottom:16px; padding:14px 16px; background:#FFFFFF; border:1px solid #E2E8F0; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-muted);">Tanggal Transaksi:</span>
                    <strong style="color:var(--text-heading);">{{ date('d F Y') }} (Hari Ini)</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-muted);">Jatuh Tempo Cicilan:</span>
                    <strong id="dispModalJatuhTempo" style="color:var(--text-heading);">-</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:8px; border-top:1px dashed #CBD5E1;">
                    <span style="color:var(--text-muted);">Porsi Pokok:</span>
                    <strong id="dispModalPokok" style="color:var(--text-heading); font-size:13.5px;">Rp 0</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--text-muted);">Jasa Bank Mini (2% Flat):</span>
                    <strong id="dispModalBunga" style="color:#047857; font-size:13.5px;">Rp 0</strong>
                </div>
            </div>

            {{-- Total Uang Tunai Diterima --}}
            <div style="background:#ECFDF5; border:1.5px solid #A7F3D0; border-radius:10px; padding:16px; text-align:center; margin-bottom:20px;">
                <div style="font-size:11.5px; font-weight:700; color:#065F46; text-transform:uppercase; letter-spacing:0.04em;">
                    Total Uang Tunai yang Diterima di Kasir
                </div>
                <div style="font-size:24px; font-weight:800; color:#0D5C46; margin:4px 0;" id="dispModalTotal">
                    Rp 0
                </div>
                <div style="font-size:11px; color:#047857;">
                    Saldo Kas Tunai akan otomatis bertambah sebesar nominal ini
                </div>
            </div>

            {{-- Tombol Aksi Modal --}}
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-ui btn-ui-outline" style="padding:9px 20px; font-weight:600;" onclick="tutupModalBayar()">
                    Batal
                </button>
                <button type="submit" class="btn-ui btn-ui-primary" style="padding:9px 24px; font-weight:800; background:#0D5C46; border-color:#0D5C46;">
                    Konfirmasi Terima Kas
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL POP-UP: RINCIAN JADWAL PAKET PINJAMAN ── --}}
<div id="modalRincianPaket" class="sipintar-modal-backdrop" style="display:none; z-index:9999; align-items:center; justify-content:center;" onclick="tutupRincianPaket(event)">
    <div class="sipintar-modal-card" style="max-width:680px; background:#FFFFFF; border-radius:14px; overflow:hidden; border:1px solid #E2E8F0;" onclick="event.stopPropagation()">
        <div style="padding:16px 22px; background:#0D5C46; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:15px; font-weight:800; color:#FFFFFF;" id="modalPaketTitle">Rincian Angsuran Paket Pinjaman</h3>
                <div style="font-size:11.5px; color:#A7F3D0; margin-top:2px;" id="modalPaketSubtitle">Seluruh jadwal cicilan bulanan</div>
            </div>
            <button type="button" onclick="tutupRincianPaket()" style="background:rgba(255,255,255,0.15); border:none; color:#FFFFFF; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:18px;">&times;</button>
        </div>
        <div style="padding:18px 22px; max-height:60vh; overflow-y:auto;" id="modalPaketBody">
            {{-- Injected dynamically --}}
        </div>
        <div style="padding:12px 22px; background:#F8FAFC; border-top:1px solid #E2E8F0; text-align:right;">
            <button type="button" class="btn-ui btn-ui-outline" onclick="tutupRincianPaket()">Tutup</button>
        </div>
    </div>
</div>

@php
    $jadwalMap = [];
    foreach ($pinjamanAktifList as $p) {
        $jadwalMap[$p->pinjaman_id] = [
            'pinjaman_id' => $p->pinjaman_id,
            'nama' => $p->nama_nasabah,
            'unit' => $p->kelas_unit,
            'nominal' => $p->nominal_pinjaman,
            'tenor' => $p->tenor,
            'jadwals' => $p->jadwals->map(function($j) {
                return [
                    'angsuran_ke' => $j->angsuran_ke,
                    'jatuh_tempo' => \Carbon\Carbon::parse($j->jatuh_tempo)->format('d/m/Y'),
                    'pokok' => $j->pokok,
                    'bunga' => $j->bunga,
                    'total' => $j->pokok + $j->bunga,
                    'status_bayar' => $j->status_bayar,
                ];
            })
        ];
    }
@endphp

<script>
const JADWAL_DATA = @json($jadwalMap);

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function bukaModalBayar(jadwalId, nama, unit, pinjamanId, angsuranKe, tenor, pokok, bunga, jatuhTempo) {
    document.getElementById('form_jadwal_id').value = jadwalId;
    document.getElementById('form_bayar_pokok').value = pokok;
    document.getElementById('form_bayar_bunga').value = bunga;

    document.getElementById('dispModalNama').innerText = nama;
    document.getElementById('dispModalUnit').innerText = unit;
    document.getElementById('dispModalPaket').innerText = `#PINJ-${String(pinjamanId).padStart(4, '0')} • Angsuran Ke-${angsuranKe} dari ${tenor} Bulan`;
    document.getElementById('dispModalJatuhTempo').innerText = jatuhTempo;
    document.getElementById('dispModalPokok').innerText = formatRupiah(pokok);
    document.getElementById('dispModalBunga').innerText = formatRupiah(bunga);
    document.getElementById('dispModalTotal').innerText = formatRupiah(pokok + bunga);

    const m = document.getElementById('modalBayarKasir');
    m.style.display = 'flex';
    m.classList.add('open');
}

function tutupModalBayar(event) {
    if (event && event.target !== event.currentTarget) return;
    const m = document.getElementById('modalBayarKasir');
    m.style.display = 'none';
    m.classList.remove('open');
}

function bukaRincianPaket(pinjamanId) {
    const data = JADWAL_DATA[pinjamanId];
    if (!data) return;

    document.getElementById('modalPaketTitle').innerText = `Jadwal Angsuran: ${data.nama}`;
    document.getElementById('modalPaketSubtitle').innerText = `#PINJ-${String(data.pinjaman_id).padStart(4, '0')} • Plafon: ${formatRupiah(data.nominal)} (${data.tenor} Bulan)`;

    let html = `
        <table class="financial-table" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th>Ke-</th>
                    <th>Jatuh Tempo</th>
                    <th style="text-align:right;">Pokok</th>
                    <th style="text-align:right;">Jasa 2%</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
    `;

    data.jadwals.forEach(j => {
        const isLunas = j.status_bayar === 'lunas';
        html += `
            <tr style="${isLunas ? 'background:#F0FDF4;' : ''}">
                <td style="font-weight:700;">Ke-${j.angsuran_ke}</td>
                <td>${j.jatuh_tempo}</td>
                <td style="text-align:right;">${formatRupiah(j.pokok)}</td>
                <td style="text-align:right;">${formatRupiah(j.bunga)}</td>
                <td style="text-align:right; font-weight:700;">${formatRupiah(j.total)}</td>
                <td style="text-align:center;">
                    <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px; ${isLunas ? 'background:#DCFCE7; color:#166534; border:1px solid #BBF7D0;' : 'background:#FEF3C7; color:#92400E; border:1px solid #FDE68A;'}">
                        ${isLunas ? 'Lunas' : 'Belum Bayar'}
                    </span>
                </td>
            </tr>
        `;
    });

    html += `</tbody></table>`;
    document.getElementById('modalPaketBody').innerHTML = html;

    const modal = document.getElementById('modalRincianPaket');
    modal.style.display = 'flex';
    modal.classList.add('open');
}

function tutupRincianPaket(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('modalRincianPaket');
    modal.style.display = 'none';
    modal.classList.remove('open');
}
</script>

@endsection
