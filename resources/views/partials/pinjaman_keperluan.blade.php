{{-- Isian keperluan / tujuan pinjaman (dipakai form nasabah pegawai & Bendahara Bank Mini) --}}
<div style="margin-bottom: 18px;">
    <label class="form-label" for="keperluan" style="display:block; font-size:13px; font-weight:700; color:var(--text-heading); margin-bottom:8px;">
        Keperluan Pinjaman <span class="required-mark" style="color:var(--color-danger, #DC2626);">*</span>
    </label>
    <textarea name="keperluan" id="keperluan" class="form-input" rows="2" maxlength="255" required
              placeholder="Contoh: Biaya pendidikan anak semester ganjil"
              style="width:100%; padding:10px 12px; font-size:13.5px; border:1.5px solid var(--border-medium, #CBD5E1); border-radius:8px; resize:vertical;">{{ old('keperluan') }}</textarea>
    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
        @foreach (['Biaya pendidikan', 'Biaya kesehatan', 'Renovasi rumah', 'Kebutuhan keluarga', 'Kendaraan', 'Modal usaha'] as $pilihan)
            <button type="button" class="chip-btn" onclick="document.getElementById('keperluan').value = '{{ $pilihan }}'; document.getElementById('keperluan').focus();">{{ $pilihan }}</button>
        @endforeach
    </div>
    <div style="font-size:11.5px; color:var(--text-muted); margin-top:6px;">Ditampilkan kepada Bendahara Bank Mini sebagai bahan penilaian kelayakan (minimal 5 karakter).</div>
    @error('keperluan')
        <div style="font-size:12px; color:#DC2626; margin-top:4px;">{{ $message }}</div>
    @enderror
</div>
