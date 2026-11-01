{{-- ── MODAL POPUP PANDUAN AKTIVASI REKENING & SETORAN AWAL ── --}}
<div id="modalAktivasiRekening" class="sipintar-modal-backdrop" onclick="tutupAktivasiModal(event)">
    <div class="sipintar-modal-card" style="max-width: 620px;" onclick="event.stopPropagation()">
        
        {{-- Header Bar --}}
        <div style="background: linear-gradient(135deg, #0D5C46 0%, #064E3B 100%); padding: 18px 22px; color: #FFFFFF; display: flex; justify-content: space-between; align-items: flex-start; gap: 14px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span style="background: rgba(245, 158, 11, 0.25); border: 1px solid #F59E0B; color: #FEF3C7; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.02em;">
                        Menunggu Aktivasi di Loket Teller
                    </span>
                    <span style="font-size: 11.5px; color: #A7F3D0; font-family: monospace;">No. Rek: {{ $rekening->no_rek ?? '-' }}</span>
                </div>
                <h2 style="font-size: 17px; font-weight: 800; color: #FFFFFF; margin: 0;">Aktivasi Rekening & Buku Tabungan</h2>
                <div style="font-size: 12px; color: #D1FAE5; margin-top: 2px;">
                    Selesaikan setoran awal di loket Bank Mini untuk mengaktifkan rekening Anda.
                </div>
            </div>
            <button type="button" onclick="tutupAktivasiModal()" style="background: rgba(255,255,255,0.15); border: none; color: #FFFFFF; width: 30px; height: 30px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; line-height: 1; flex-shrink: 0;" title="Tutup">
                &times;
            </button>
        </div>

        {{-- Modal Body (Scrollable) --}}
        <div style="padding: 20px 22px; overflow-y: auto; max-height: calc(85vh - 130px); display: flex; flex-direction: column; gap: 18px;">
            
            {{-- Identitas Rekening --}}
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Pemegang Rekening</div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--text-heading); margin-top: 2px;">{{ $nasabah->nama_nasabah ?? auth()->user()->name }}</div>
                    <div style="font-size: 11.5px; color: var(--text-muted);">{{ $nasabah->kelas_unit ?? (($nasabah->jenis_nasabah ?? '') === 'pegawai' ? 'Pegawai Sekolah' : 'Siswa') }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Status Layanan</div>
                    <div style="font-size: 13.5px; font-weight: 700; color: #D97706; margin-top: 2px;">Menunggu Setoran Awal</div>
                    <div style="font-size: 11.5px; color: var(--text-muted);">Saldo: Rp 0 (Belum Aktif)</div>
                </div>
            </div>

            {{-- 1. Syarat yang Harus Dibawa --}}
            <div>
                <div style="font-size: 13px; font-weight: 700; color: var(--text-heading); margin-bottom: 8px;">
                    1. Persyaratan yang Dibawa ke Loket
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D5C46" stroke-width="2" style="margin-top: 2px; flex-shrink: 0;"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                        <div style="font-size: 12.5px; color: var(--text-body); line-height: 1.4;">
                            <strong style="color: #0F172A;">Setoran Awal Tunai: Rp 10.000</strong><br>
                            <span style="color: var(--text-muted); font-size: 11.5px;">Uang ini langsung masuk sebagai saldo tabungan pertama Anda dan dapat ditarik kembali.</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0284C7" stroke-width="2" style="margin-top: 2px; flex-shrink: 0;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><circle cx="10" cy="13" r="2"/><line x1="14" y1="17" x2="6" y2="17"/></svg>
                        <div style="font-size: 12.5px; color: var(--text-body); line-height: 1.4;">
                            <strong style="color: #0F172A;">Kartu Identitas Sekolah</strong><br>
                            <span style="color: var(--text-muted); font-size: 11.5px;">
                                @if (($nasabah->jenis_nasabah ?? 'non_pegawai') === 'pegawai')
                                    Tunjukkan Kartu Pegawai / KTP (NIP: {{ $user->no_induk ?? '-' }}) kepada Teller.
                                @else
                                    Tunjukkan Kartu Pelajar (NIS: {{ $user->no_induk ?? '-' }}) kepada Teller.
                                @endif
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" style="margin-top: 2px; flex-shrink: 0;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <div style="font-size: 12.5px; color: var(--text-body); line-height: 1.4;">
                            <strong style="color: #0F172A;">Nomor HP Terdaftar: {{ auth()->user()->no_hp ?? '-' }}</strong><br>
                            <span style="color: var(--text-muted); font-size: 11.5px;">Gunakan nomor ini untuk konfirmasi data jika diperlukan petugas loket.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Alur Pelayanan di Loket --}}
            <div>
                <div style="font-size: 13px; font-weight: 700; color: var(--text-heading); margin-bottom: 8px;">
                    2. Alur Pelayanan di Loket Bank Mini
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12.5px; color: var(--text-body); line-height: 1.45;">
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <span style="font-weight: 700; color: #0D5C46;">1.</span>
                        <div>Kunjungi ruang Bank Mini (Lab Perbankan SMKN 1 Lemahabang) pada jam 07.30 – 14.00 WIB.</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <span style="font-weight: 700; color: #0D5C46;">2.</span>
                        <div>Temui Teller / Siswa Praktik, lalu sebutkan nama atau nomor rekening: <code style="font-size:12px; font-weight:700;">{{ $rekening->no_rek ?? '-' }}</code>.</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <span style="font-weight: 700; color: #0D5C46;">3.</span>
                        <div>Serahkan setoran awal Rp 10.000 untuk dihitung dan dibukukan ke kas Bank Mini.</div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: flex-start;">
                        <span style="font-weight: 700; color: #0D5C46;">4.</span>
                        <div>Teller mengonfirmasi aktivasi di sistem dan menyerahkan buku tabungan fisik Anda. Rekening langsung aktif.</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer Bar --}}
        <div style="padding: 12px 22px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div style="font-size: 11.5px; color: var(--text-muted);">
                Lokasi: Lab Perbankan / Bank Mini SMKN 1 Lemahabang
            </div>
            <button type="button" onclick="tutupAktivasiModal()" class="btn-ui btn-ui-primary" style="padding: 7px 20px; font-weight: 700; font-size: 13px;">
                Tutup
            </button>
        </div>

    </div>
</div>

<script>
function openAktivasiModal() {
    const m = document.getElementById('modalAktivasiRekening');
    if (m) m.classList.add('open');
}

function tutupAktivasiModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const m = document.getElementById('modalAktivasiRekening');
    if (m) m.classList.remove('open');
}

// Buka otomatis satu kali saat nasabah pending membuka dashboard
document.addEventListener('DOMContentLoaded', function() {
    if (!sessionStorage.getItem('sipintar_modal_aktivasi_shown_{{ auth()->id() }}')) {
        openAktivasiModal();
        sessionStorage.setItem('sipintar_modal_aktivasi_shown_{{ auth()->id() }}', 'true');
    }
});
</script>
