{{--
    Toolbar daftar seragam: pencarian di server + tab status berjumlah.

    Parameter:
      $id          : awalan id elemen (unik per halaman), mis. 'paket'
      $tabs        : ['kunci' => ['label' => 'Perlu Dibayar', 'jumlah' => 12, 'nada' => 'amber'|'red'|'green'|null], ...]
      $tab         : kunci tab aktif
      $q           : kata pencarian aktif
      $placeholder : teks petunjuk pencarian
      $pageName    : nama parameter halaman yang di-reset saat mencari / pindah tab (default 'page')
--}}
@php
    $pageName = $pageName ?? 'page';
    $tabs = $tabs ?? [];
    $tetap = collect(request()->query())->except(['q', 'tab', $pageName]);
    $urlTab = fn ($kunci) => request()->url() . '?' . http_build_query($tetap->merge(array_filter(['tab' => $kunci, 'q' => $q]))->all());
    $urlReset = request()->url() . '?' . http_build_query($tetap->merge(['tab' => $tab])->all());
@endphp

<form method="GET" action="{{ request()->url() }}" class="daftar-toolbar" id="{{ $id }}-toolbar" role="search">
    @foreach ($tetap as $nama => $nilai)
        @if (! is_array($nilai))
            <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
        @endif
    @endforeach
    @if ($tab)
        <input type="hidden" name="tab" value="{{ $tab }}">
    @endif

    <div class="daftar-cari">
        <svg class="daftar-cari-ikon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
        <input type="search" name="q" id="{{ $id }}-cari" value="{{ $q }}" placeholder="{{ $placeholder }}"
               autocomplete="off" enterkeyhint="search" aria-label="{{ $placeholder }}">
        @if ($q !== '' && $q !== null)
            <a href="{{ $urlReset }}" class="daftar-cari-hapus" aria-label="Hapus pencarian" title="Hapus pencarian">&times;</a>
        @endif
        <button type="submit" class="daftar-cari-btn">Cari</button>
    </div>

    @if (count($tabs))
        <nav class="daftar-tab" aria-label="Saring status">
            @foreach ($tabs as $kunci => $t)
                <a href="{{ $urlTab($kunci) }}" id="{{ $id }}-tab-{{ $kunci }}"
                   class="daftar-tab-item {{ $tab === $kunci ? 'aktif' : '' }}"
                   @if ($tab === $kunci) aria-current="page" @endif>
                    {{ $t['label'] }}
                    <span class="daftar-tab-jumlah {{ $t['nada'] ?? '' }}">{{ number_format($t['jumlah'], 0, ',', '.') }}</span>
                </a>
            @endforeach
        </nav>
    @endif
</form>

@if ($q !== '' && $q !== null)
    <div class="daftar-info-cari" role="status">Hasil pencarian "<strong>{{ $q }}</strong>"</div>
@endif
