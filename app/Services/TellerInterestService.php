<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TellerInterestService
{
    public function activeAccounts(): Collection
    {
        return DB::table('tblREKENING as r')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->where('r.status_rekening', 'aktif')
            ->select('r.rekening_id', 'r.no_rek', 'r.saldo', 'n.nama_nasabah', 'n.jenis_nasabah')
            ->orderBy('r.no_rek')
            ->get();
    }

    public function preview(?string $noRek): ?object
    {
        if (!$noRek) {
            return null;
        }

        $rekening = DB::table('tblREKENING as r')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->where('r.status_rekening', 'aktif')
            ->where('r.no_rek', $noRek)
            ->select('r.rekening_id', 'r.no_rek', 'r.saldo', 'n.nama_nasabah', 'n.jenis_nasabah')
            ->first();

        if (!$rekening) {
            return null;
        }

        $kategori = $rekening->jenis_nasabah === 'pegawai' ? 'pegawai' : 'siswa';
        $persen = (float) config("sipintar.bunga_tabungan.{$kategori}");
        $bunga = round((float) $rekening->saldo * $persen, 2);
        $rekening->kategori_bunga = $kategori;
        $rekening->persen_bunga = $persen * 100;
        $rekening->nominal_bunga = $bunga;
        $rekening->saldo_setelah_bunga = (float) $rekening->saldo + $bunga;

        return $rekening;
    }

    public function process(int $rekeningId, int $tellerId): void
    {
        DB::transaction(function () use ($rekeningId, $tellerId) {
            $periode = PeriodeLockService::assertPeriodeTerbuka(now());
            $jenisBunga = DB::table('tblJENIS_TRANSAKSI')->where('nama_jenis', 'jasa_tabungan')->first();

            if (!$jenisBunga) {
                throw ValidationException::withMessages([
                    'bunga' => 'Jenis transaksi jasa_tabungan belum tersedia.',
                ]);
            }

            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
            $rekening = DB::table('tblREKENING as r')
                ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
                ->where('r.rekening_id', $rekeningId)
                ->where('r.status_rekening', 'aktif')
                ->lockForUpdate()
                ->first();

            if (!$rekening) {
                throw ValidationException::withMessages(['rekening' => 'Rekening aktif tidak ditemukan.']);
            }

            if (DB::table('tblTRANSAKSI')
                ->where('rekening_id', $rekeningId)
                ->where('jenis_transaksi_id', $jenisBunga->jenis_transaksi_id)
                ->whereBetween('tgl_transaksi', [$start, $end])
                ->exists()) {
                throw ValidationException::withMessages(['rekening' => 'Bunga rekening ini sudah diproses pada periode berjalan.']);
            }

            $kategori = $rekening->jenis_nasabah === 'pegawai' ? 'pegawai' : 'siswa';
            $persen = (float) config("sipintar.bunga_tabungan.{$kategori}");
            $bunga = round((float) $rekening->saldo * $persen, 2);
            if ($bunga <= 0) {
                throw ValidationException::withMessages(['rekening' => 'Saldo rekening belum menghasilkan bunga.']);
            }

            $saldoAkhir = (float) $rekening->saldo + $bunga;
            $transaksiId = DB::table('tblTRANSAKSI')->insertGetId([
                'rekening_id' => $rekeningId,
                'jenis_transaksi_id' => $jenisBunga->jenis_transaksi_id,
                'periode_id' => $periode->periode_id,
                'draft_id' => null,
                'user_id' => $tellerId,
                'debit' => 0,
                'kredit' => $bunga,
                'saldo_akhir' => $saldoAkhir,
                'tgl_transaksi' => now(),
            ]);

            DB::table('tblREKENING')->where('rekening_id', $rekeningId)->update(['saldo' => $saldoAkhir]);

            AuditLogService::log(
                aksi: 'HITUNG_BUNGA_TABUNGAN',
                entitas: 'tblTRANSAKSI',
                entitasId: $transaksiId,
                dataBaru: [
                    'rekening_id' => $rekeningId,
                    'jenis_nasabah' => $rekening->jenis_nasabah,
                    'persen_bunga' => $persen * 100,
                    'nominal_bunga' => $bunga,
                ]
            );
        });
    }
}