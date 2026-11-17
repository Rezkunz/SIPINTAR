<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data tunjangan pegawai per bulan dari Bendahara Sekolah (FR-16).
 *
 * - Penilaian kelayakan memakai data terbaru (gaji + tunjangan) sebagai penghasilan.
 * - Rencana potongan memakai "tunjangan bersih" (tunjangan - potongan lain, mis. pajak)
 *   untuk memperingatkan pegawai yang tunjangannya tidak cukup untuk dipotong angsuran.
 */
class DataTunjanganService
{
    /** Data dianggap usang bila lebih dari jumlah bulan ini dari bulan acuan. */
    public const BATAS_BULAN_USANG = 1;

    /**
     * Data terbaru per pegawai pada atau sebelum bulan acuan.
     *
     * @param  array<int>  $nasabahIds
     * @return Collection<int, object> dikunci oleh nasabah_id
     */
    public static function terbaruUntuk(array $nasabahIds, ?Carbon $acuan = null): Collection
    {
        if (empty($nasabahIds)) {
            return collect();
        }

        $acuan = ($acuan ?? now())->copy()->startOfMonth();
        $kunci = $acuan->year * 100 + $acuan->month;

        return DB::table('tblDATA_TUNJANGAN')
            ->whereIn('nasabah_id', $nasabahIds)
            ->whereRaw('(tahun * 100 + bulan) <= ?', [$kunci])
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get()
            ->unique('nasabah_id')
            ->keyBy('nasabah_id')
            ->map(function ($d) use ($acuan) {
                $bulanData = Carbon::create($d->tahun, $d->bulan, 1);
                $d->periode_label = $bulanData->translatedFormat('F Y');
                $d->selisih_bulan = (int) round(abs($bulanData->diffInMonths($acuan)));
                $d->usang = $d->selisih_bulan > self::BATAS_BULAN_USANG;
                $d->penghasilan = (float) $d->gaji_pokok + (float) $d->tunjangan;
                $d->tunjangan_bersih = max(0, (float) $d->tunjangan - (float) $d->potongan_lain);

                return $d;
            });
    }

    /**
     * Simpan/perbarui data satu pegawai untuk satu bulan.
     * Master gaji & tunjangan di tblNASABAH ikut diperbarui bila ini data paling baru.
     */
    public static function simpan(int $nasabahId, int $tahun, int $bulan, array $nilai, int $userId): void
    {
        $data = [
            'gaji_pokok'    => (float) ($nilai['gaji_pokok'] ?? 0),
            'tunjangan'     => (float) ($nilai['tunjangan'] ?? 0),
            'potongan_lain' => (float) ($nilai['potongan_lain'] ?? 0),
            'keterangan'    => isset($nilai['keterangan']) && trim((string) $nilai['keterangan']) !== ''
                ? mb_substr(strip_tags(trim((string) $nilai['keterangan'])), 0, 255) : null,
            'user_id'       => $userId,
            'updated_at'    => now(),
        ];

        $ada = DB::table('tblDATA_TUNJANGAN')
            ->where('nasabah_id', $nasabahId)->where('tahun', $tahun)->where('bulan', $bulan)
            ->exists();

        if ($ada) {
            DB::table('tblDATA_TUNJANGAN')
                ->where('nasabah_id', $nasabahId)->where('tahun', $tahun)->where('bulan', $bulan)
                ->update($data);
        } else {
            DB::table('tblDATA_TUNJANGAN')->insert($data + [
                'nasabah_id' => $nasabahId,
                'tahun'      => $tahun,
                'bulan'      => $bulan,
                'created_at' => now(),
            ]);
        }

        $terbaru = DB::table('tblDATA_TUNJANGAN')
            ->where('nasabah_id', $nasabahId)
            ->orderByDesc('tahun')->orderByDesc('bulan')
            ->first(['tahun', 'bulan']);

        if ($terbaru && (int) $terbaru->tahun === $tahun && (int) $terbaru->bulan === $bulan) {
            DB::table('tblNASABAH')->where('nasabah_id', $nasabahId)->update([
                'gaji_pokok' => $data['gaji_pokok'],
                'tunjangan'  => $data['tunjangan'],
            ]);
        }
    }
}
