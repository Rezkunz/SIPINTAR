<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian & tab status di sisi server untuk daftar panjang (ratusan data).
 * Dipakai bersama partial resources/views/partials/daftar_toolbar.blade.php.
 */
trait DaftarTerfilter
{
    /**
     * Saring berdasarkan kata kunci pada beberapa kolom teks, atau ID bila kata kunci berupa nomor
     * (mis. "14", "#PINJ-0014", "pinj14").
     */
    protected function filterCari($query, string $q, array $kolomTeks, ?string $kolomId = null)
    {
        $operator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $pola = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $nomor = preg_match('/^#?(?:[a-z]+-?)?0*(\d{1,9})$/i', $q, $m) ? (int) $m[1] : null;

        return $query->where(function ($w) use ($kolomTeks, $operator, $pola, $kolomId, $nomor) {
            foreach ($kolomTeks as $kolom) {
                $w->orWhere($kolom, $operator, $pola);
            }
            if ($kolomId && $nomor !== null) {
                $w->orWhere($kolomId, $nomor);
            }
        });
    }

    /**
     * Tab yang dibuka: pilihan pengguna bila valid. Tanpa pilihan, buka tab default ("perlu tindakan")
     * kecuali kosong, maka buka tab terakhir ("semua") agar halaman tidak tampak kosong.
     */
    protected function tabAktif(Request $request, array $kunci, Collection $jumlah, string $default): string
    {
        $diminta = (string) $request->query('tab', '');
        if (in_array($diminta, $kunci, true)) {
            return $diminta;
        }

        return ($jumlah[$default] ?? 0) > 0 ? $default : end($kunci);
    }
}
