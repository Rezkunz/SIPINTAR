<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aktivasi rekening oleh Teller dulu menambah tblKAS.saldo tanpa mencatat tblMUTASI_KAS,
 * sehingga saldo kas per tanggal (Neraca, Tutup Periode) meleset untuk bulan-bulan lampau.
 * Migrasi ini melengkapi mutasi kas untuk setoran awal yang belum punya pasangan mutasi.
 * tblKAS.saldo TIDAK diubah karena saldo tersebut sudah bertambah saat aktivasi.
 */
return new class extends Migration
{
    private const PENANDA = ' (sinkronisasi data lama)';

    public function up(): void
    {
        $kasTunai = DB::table('tblKAS')->where('jenis_kas', 'kas')->orderBy('kas_id')->value('kas_id');
        if (!$kasTunai) {
            return;
        }

        // Satu-satunya alur yang menulis tblTRANSAKSI tanpa draft adalah aktivasi rekening Teller
        $setoranTanpaMutasi = DB::table('tblTRANSAKSI as t')
            ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->leftJoin('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->leftJoin('tblMUTASI_KAS as mk', 't.transaksi_id', '=', 'mk.transaksi_id')
            ->whereNull('mk.mutasi_kas_id')
            ->whereNull('t.draft_id')
            ->where('t.jenis_transaksi_id', 1)
            ->where('t.kredit', '>', 0)
            ->get(['t.transaksi_id', 't.periode_id', 't.user_id', 't.kredit', 't.tgl_transaksi', 'r.no_rek', 'n.nama_nasabah']);

        foreach ($setoranTanpaMutasi as $t) {
            DB::table('tblMUTASI_KAS')->insert([
                'kas_id'       => $kasTunai,
                'periode_id'   => $t->periode_id,
                'user_id'      => $t->user_id,
                'transaksi_id' => $t->transaksi_id,
                'tgl_mutasi'   => substr((string) $t->tgl_transaksi, 0, 10),
                'arah'         => 'masuk',
                'nominal'      => $t->kredit,
                'uraian'       => "Setoran awal tabungan {$t->no_rek}" . ($t->nama_nasabah ? " a.n. {$t->nama_nasabah}" : '') . self::PENANDA,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('tblMUTASI_KAS')
            ->whereNotNull('transaksi_id')
            ->where('uraian', 'like', '%' . self::PENANDA)
            ->delete();
    }
};
