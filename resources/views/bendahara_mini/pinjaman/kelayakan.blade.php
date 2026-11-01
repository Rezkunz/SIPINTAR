@extends('layouts.app')

@section('title', 'Penilaian Kelayakan Kredit')
@section('breadcrumb', 'Penilaian Kelayakan')

@section('content')

{{-- ── HERO BANNER ── --}}
<div class="dashboard-welcome-banner">
    <div class="welcome-content">
        <h1>Penilaian Kelayakan Pinjaman Pegawai</h1>
        <p>Analisis kapasitas pembayaran calon peminjam berdasarkan penghasilan rutin dan rekam jejak kedisiplinan.</p>
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

{{-- ── ANTREAN PERLU PENILAIAN ── --}}
<div class="content-card" style="margin-bottom: 24px;">
    <div class="card-header-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <h2 class="card-heading">Antrean Permohonan Pinjaman Belum Dinilai</h2>
            <div style="font-size:12px; color:var(--text-muted);">Persetujuan komite wajib disimpan sebagai dokumentasi audit kredit</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="position:relative; width:250px;">
                <input type="text" class="form-input" placeholder="Cari nama atau unit peminjam..." 
                       oninput="filterTableRows(this, 'tableKelayakan')"
                       style="padding:6px 12px; font-size:12.5px; border-radius:6px; width:100%;">
            </div>
            <span class="badge-pill amber">{{ $pinjamanBelumDinilai->count() }} Menunggu Verifikasi</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="financial-table" id="tableKelayakan">
            <thead>
                <tr>
                    <th>Tgl Pengajuan</th>
                    <th>Peminjam & Unit</th>
                    <th style="text-align: right;">Gaji + Tunjangan</th>
                    <th style="text-align: right;">Plafon Diajukan</th>
                    <th>Tenor</th>
                    <th style="text-align: right;">Est. Cicilan/Bln</th>
                    <th style="text-align: center;">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pinjamanBelumDinilai as $p)
                    @php
                        $totalPenghasilan = ($p->gaji_pokok ?? 0) + ($p->tunjangan ?? 0);
                        $pokokPerBln = floor($p->nominal_pinjaman / $p->tenor);
                        $jasaPerBln = round(($p->nominal_pinjaman * 2.0) / 100);
                        $estCicilan = $pokokPerBln + $jasaPerBln;
                        $rasioDSR = $totalPenghasilan > 0 ? round(($estCicilan / $totalPenghasilan) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($p->tgl_pengajuan)->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $p->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $p->kelas_unit ?? 'Pegawai' }}</div>
                        </td>
                        <td style="text-align: right;">
                            <div style="font-weight:600; color:var(--text-heading);">Rp {{ number_format($totalPenghasilan, 0, ',', '.') }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">Gaji: Rp {{ number_format($p->gaji_pokok, 0, ',', '.') }}</div>
                        </td>
                        <td style="text-align: right; font-weight:700; color:var(--text-heading);">
                            Rp {{ number_format($p->nominal_pinjaman, 0, ',', '.') }}
                        </td>
                        <td>{{ $p->tenor }} Bulan</td>
                        <td style="text-align: right;">
                            <div style="font-weight:700; color:var(--primary, #0284c7);">Rp {{ number_format($estCicilan, 0, ',', '.') }}</div>
                            <div style="font-size:11px; color:{{ $rasioDSR > 35 ? '#b91c1c' : '#15803d' }};">
                                DSR: {{ $rasioDSR }}%
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-ui btn-ui-sm btn-ui-primary"
                                    onclick="openAssessmentModal({{ $p->pinjaman_id }}, '{{ addslashes($p->nama_nasabah) }}', '{{ addslashes($p->kelas_unit ?? 'Pegawai') }}', {{ $totalPenghasilan }}, {{ $p->nominal_pinjaman }}, {{ $p->tenor }}, {{ $estCicilan }})">
                                Beri Penilaian
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-placeholder" style="padding:32px 16px; text-align:center;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 8px; color:var(--text-faint); display:block;"><polyline points="20 6 9 17 4 12"/></svg>
                                Seluruh pengajuan pinjaman saat ini telah selesai dinilai kelayakannya.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── 10 RIWAYAT PENILAIAN TERAKHIR ── --}}
<div class="content-card">
    <div class="card-header-bar">
        <h2 class="card-heading">Riwayat Keputusan Kelayakan Terakhir</h2>
    </div>
    <div class="table-responsive">
        <table class="financial-table">
            <thead>
                <tr>
                    <th>Tgl Dinilai</th>
                    <th>Nama Peminjam</th>
                    <th style="text-align: right;">Plafon</th>
                    <th style="text-align: right;">Penghasilan Diverifikasi</th>
                    <th style="text-align: center;">Hasil</th>
                    <th>Catatan Pertimbangan Komite</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pinjamanSudahDinilai as $r)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($r->tgl_nilai)->format('d/m/Y') }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-heading);">{{ $r->nama_nasabah }}</div>
                            <div style="font-size:11px; color:var(--text-muted);">{{ $r->kelas_unit }}</div>
                        </td>
                        <td style="text-align: right; font-weight:600;">
                            Rp {{ number_format($r->nominal_pinjaman, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right;">
                            Rp {{ number_format($r->penghasilan, 0, ',', '.') }}
                        </td>
                        <td style="text-align: center;">
                            @if ($r->hasil_kelayakan === 'layak')
                                <span class="badge-pill green">Layak</span>
                            @else
                                <span class="badge-pill red">Tidak Layak (Ditolak)</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--text-muted); max-width:280px;">
                            {{ $r->catatan }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-placeholder" style="padding:24px 16px; text-align:center;">
                                Belum ada riwayat penilaian kelayakan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── MODAL PENILAIAN KELAYAKAN (MODERN BANKING MODAL) ── --}}
<div id="assessmentModal" style="position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); display:none; justify-content:center; align-items:center; z-index:9999; padding:20px;">
    <div style="background:#FFFFFF; border-radius:16px; width:100%; max-width:580px; box-shadow:0 25px 50px -12px rgba(15,23,42,0.25); overflow:hidden; border:1px solid var(--border-subtle); display:flex; flex-direction:column; max-height:92vh;">
        
        {{-- Header Modal --}}
        <div style="padding:18px 24px; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; background:#FFFFFF;">
            <div>
                <div style="font-weight:800; font-size:16px; color:#0F172A; letter-spacing:-0.01em;">Form Evaluasi Kelayakan Kredit</div>
                <div style="font-size:12px; color:#64748B; margin-top:2px;">Dokumentasi audit persetujuan komite kredit Bank Mini</div>
            </div>
            <button type="button" onclick="closeAssessmentModal()" title="Tutup" style="width:32px; height:32px; border-radius:8px; border:1px solid #E2E8F0; background:#F8FAFC; color:#64748B; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Form Body (Scrollable if needed) --}}
        <form id="assessmentForm" method="POST" action="" style="overflow-y:auto; padding:24px; display:flex; flex-direction:column; gap:18px; margin:0;">
            @csrf

            {{-- Ringkasan Pegawai & Pinjaman --}}
            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:16px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    <div id="modalInitials" style="width:42px; height:42px; border-radius:50%; background:#0D5C46; color:#FFFFFF; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        PG
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div id="modalNama" style="font-weight:700; font-size:15px; color:#0F172A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            Nama Pegawai
                        </div>
                        <div id="modalUnit" style="font-size:12px; color:#64748B; margin-top:2px;">
                            Unit Kerja
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; background:#FFFFFF; border:1px solid #E2E8F0; border-radius:8px; padding:10px 12px; text-align:center;">
                    <div>
                        <div style="font-size:10.5px; color:#64748B; font-weight:600; text-transform:uppercase;">Plafon Pinjaman</div>
                        <div id="modalPlafon" style="font-size:13px; font-weight:700; color:#0F172A; margin-top:2px;">Rp 0</div>
                    </div>
                    <div>
                        <div style="font-size:10.5px; color:#64748B; font-weight:600; text-transform:uppercase;">Jangka Waktu</div>
                        <div id="modalTenor" style="font-size:13px; font-weight:700; color:#0F172A; margin-top:2px;">0 Bulan</div>
                    </div>
                    <div>
                        <div style="font-size:10.5px; color:#64748B; font-weight:600; text-transform:uppercase;">Est. Cicilan/Bln</div>
                        <div id="modalCicilan" style="font-size:13px; font-weight:800; color:#0D5C46; margin-top:2px;">Rp 0</div>
                    </div>
                </div>
            </div>

            {{-- Input Pendapatan Bulanan Terverifikasi --}}
            <div>
                <label for="modalPenghasilan" style="display:block; font-size:13px; font-weight:700; color:#0F172A; margin-bottom:6px;">
                    Pendapatan Bulanan Terverifikasi (Rp) <span style="color:#DC2626;">*</span>
                </label>
                <div style="position:relative;">
                    <span style="position:absolute; left:14px; top:50%; transform:translateY(-50%); font-weight:700; font-size:14px; color:#64748B;">Rp</span>
                    <input type="number" name="penghasilan" id="modalPenghasilan" required min="0" step="10000" oninput="updateModalDsr()"
                           style="display:block; width:100%; padding:10px 14px 10px 44px; font-size:15px; font-weight:700; border:1.5px solid #CBD5E1; border-radius:8px; color:#0F172A; box-sizing:border-box;">
                </div>
                <div style="font-size:11.5px; color:#64748B; margin-top:5px;">Gaji pokok ditambah tunjangan tetap peminjam per bulan</div>

                {{-- Live DSR Bar --}}
                <div style="background:#F1F5F9; border-radius:8px; padding:10px 12px; margin-top:10px; border:1px solid #E2E8F0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <span style="font-size:11.5px; font-weight:700; color:#334155;">Analisis Rasio Beban Cicilan (DSR):</span>
                        <span id="modalDsrBadge" style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px; background:#D1FAE5; color:#065F46;">
                            Sangat Aman
                        </span>
                    </div>
                    <div style="width:100%; height:6px; background:#E2E8F0; border-radius:999px; overflow:hidden;">
                        <div id="modalDsrBar" style="width:10%; height:100%; background:#059669; transition:all 0.2s ease;"></div>
                    </div>
                    <div id="modalDsrText" style="font-size:11px; color:#64748B; margin-top:4px;">
                        Cicilan menyerap beban yang aman dari penghasilan.
                    </div>
                </div>
            </div>

            {{-- Keputusan Kelayakan --}}
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:#0F172A; margin-bottom:6px;">
                    Keputusan Kelayakan Komite <span style="color:#DC2626;">*</span>
                </label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <label id="labelLayak" style="border:1.5px solid #0D5C46; background:#F0FDF4; border-radius:10px; padding:12px 14px; display:flex; align-items:flex-start; gap:10px; cursor:pointer; transition:all 0.15s ease;">
                        <input type="radio" name="hasil_kelayakan" id="radioLayak" value="layak" checked required onchange="toggleKeputusanVisual()" style="margin-top:3px;">
                        <div>
                            <div style="font-weight:700; color:#166534; font-size:13.5px;">LAYAK (Disetujui)</div>
                            <div style="font-size:11.5px; color:#15803d; margin-top:2px;">Plafon disetujui, siap diteruskan ke pencairan dana</div>
                        </div>
                    </label>
                    <label id="labelTidakLayak" style="border:1.5px solid #CBD5E1; background:#FFFFFF; border-radius:10px; padding:12px 14px; display:flex; align-items:flex-start; gap:10px; cursor:pointer; transition:all 0.15s ease;">
                        <input type="radio" name="hasil_kelayakan" id="radioTidakLayak" value="tidak_layak" required onchange="toggleKeputusanVisual()" style="margin-top:3px;">
                        <div>
                            <div style="font-weight:700; color:#991B1B; font-size:13.5px;">TIDAK LAYAK</div>
                            <div style="font-size:11.5px; color:#B91C1C; margin-top:2px;">Pengajuan ditolak, arsipkan catatan evaluasi</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Catatan Evaluasi Kredit --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <label for="catatan" style="font-size:13px; font-weight:700; color:#0F172A; margin:0;">
                        Catatan Evaluasi Kredit <span style="color:#DC2626;">*</span>
                    </label>
                    <span style="font-size:11px; color:#64748B;">Klik rekomendasi cepat:</span>
                </div>

                {{-- Quick Presets --}}
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px;">
                    <button type="button" class="preset-chip" onclick="setCatatanPreset('Rasio cicilan aman (< 30% dari total pendapatan). Rekam jejak peminjam disiplin.')">
                        Cicilan Aman & Disiplin
                    </button>
                    <button type="button" class="preset-chip" onclick="setCatatanPreset('Kapasitas cicilan mendekati batas wajar 40%, disetujui dengan pengawasan payroll.')">
                        Batas Wajar 40%
                    </button>
                    <button type="button" class="preset-chip" onclick="setCatatanPreset('Ditolak: Beban cicilan melebihi toleransi komite kredit (> 40% dari total pendapatan).')">
                        Ditolak DSR Tinggi
                    </button>
                </div>

                <textarea name="catatan" id="catatan" rows="3" required
                          placeholder="Contoh: Rasio cicilan aman (< 30% dari total pendapatan). Rekam jejak disiplin."
                          style="display:block; width:100%; padding:10px 14px; font-size:13px; border:1.5px solid #CBD5E1; border-radius:8px; color:#0F172A; box-sizing:border-box; line-height:1.5; font-family:inherit;"></textarea>
            </div>

            {{-- Footer Aksi --}}
            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:14px; border-top:1px solid #E2E8F0; margin-top:4px;">
                <button type="button" onclick="closeAssessmentModal()" class="btn-ui btn-ui-outline" style="padding:10px 18px; font-size:13px;">
                    Batal
                </button>
                <button type="submit" class="btn-ui btn-ui-primary" style="padding:10px 22px; font-size:13.5px; font-weight:800; background:#0D5C46; border-color:#0D5C46;">
                    Simpan Keputusan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.preset-chip {
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.preset-chip:hover {
    border-color: #0D5C46;
    background: #ECFDF5;
    color: #0D5C46;
}
</style>

<script>
let currentModalCicilan = 0;

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function openAssessmentModal(pinjamanId, nama, unit, totalGaji, plafon, tenor, cicilan) {
    const modal = document.getElementById('assessmentModal');
    const form = document.getElementById('assessmentForm');

    form.action = `/bbm/pinjaman/kelayakan/${pinjamanId}`;
    
    // Inisial Nama
    const parts = nama.trim().split(' ');
    let initials = '';
    for (let i = 0; i < Math.min(2, parts.length); i++) {
        initials += parts[i].charAt(0).toUpperCase();
    }
    document.getElementById('modalInitials').innerText = initials || 'PG';
    document.getElementById('modalNama').innerText = nama;
    document.getElementById('modalUnit').innerText = unit || 'Pegawai SMKN 1 Lemahabang';

    document.getElementById('modalPlafon').innerText = formatRupiah(plafon);
    document.getElementById('modalTenor').innerText = tenor + ' Bulan';
    document.getElementById('modalCicilan').innerText = formatRupiah(cicilan) + ' / bln';

    document.getElementById('modalPenghasilan').value = totalGaji;
    currentModalCicilan = cicilan;

    // Reset default
    document.getElementById('radioLayak').checked = true;
    toggleKeputusanVisual();
    document.getElementById('catatan').value = 'Rasio cicilan aman (< 30% dari total pendapatan). Rekam jejak peminjam disiplin.';

    updateModalDsr();

    modal.style.display = 'flex';
}

function closeAssessmentModal() {
    document.getElementById('assessmentModal').style.display = 'none';
}

function toggleKeputusanVisual() {
    const isLayak = document.getElementById('radioLayak').checked;
    const labelLayak = document.getElementById('labelLayak');
    const labelTidakLayak = document.getElementById('labelTidakLayak');

    if (isLayak) {
        labelLayak.style.borderColor = '#0D5C46';
        labelLayak.style.backgroundColor = '#F0FDF4';
        labelTidakLayak.style.borderColor = '#CBD5E1';
        labelTidakLayak.style.backgroundColor = '#FFFFFF';
    } else {
        labelTidakLayak.style.borderColor = '#DC2626';
        labelTidakLayak.style.backgroundColor = '#FEF2F2';
        labelLayak.style.borderColor = '#CBD5E1';
        labelLayak.style.backgroundColor = '#FFFFFF';
    }
}

function setCatatanPreset(text) {
    document.getElementById('catatan').value = text;
    if (text.includes('Ditolak')) {
        document.getElementById('radioTidakLayak').checked = true;
    } else {
        document.getElementById('radioLayak').checked = true;
    }
    toggleKeputusanVisual();
}

function updateModalDsr() {
    const penghasilan = parseFloat(document.getElementById('modalPenghasilan').value) || 0;
    const badge = document.getElementById('modalDsrBadge');
    const bar = document.getElementById('modalDsrBar');
    const text = document.getElementById('modalDsrText');

    if (penghasilan > 0 && currentModalCicilan > 0) {
        const dsr = (currentModalCicilan / penghasilan) * 100;
        const dsrPct = Math.round(dsr * 10) / 10;
        const barWidth = Math.min(Math.round(dsrPct), 100);

        bar.style.width = barWidth + '%';

        if (dsrPct <= 30) {
            badge.innerText = 'Sangat Aman (' + dsrPct + '%)';
            badge.style.background = '#D1FAE5';
            badge.style.color = '#065F46';
            bar.style.backgroundColor = '#059669';
            text.innerText = 'Cicilan menyerap ' + dsrPct + '% dari total pendapatan bulanan. Sangat layak disetujui.';
        } else if (dsrPct <= 40) {
            badge.innerText = 'Wajar (' + dsrPct + '%)';
            badge.style.background = '#FEF3C7';
            badge.style.color = '#92400E';
            bar.style.backgroundColor = '#D97706';
            text.innerText = 'Cicilan menyerap ' + dsrPct + '% dari total pendapatan bulanan (dalam batas aman komite).';
        } else {
            badge.innerText = 'Beban Tinggi (' + dsrPct + '%)';
            badge.style.background = '#FEE2E2';
            badge.style.color = '#991B1B';
            bar.style.backgroundColor = '#DC2626';
            text.innerText = 'Peringatan: Cicilan menyerap ' + dsrPct + '% (> 40% batas aman). Perlu evaluasi komite.';
        }
    } else {
        badge.innerText = '0%';
        bar.style.width = '0%';
        text.innerText = 'Masukkan pendapatan terverifikasi untuk menganalisis rasio DSR.';
    }
}
</script>

@endsection
