<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audit konsistensi data modul Pinjaman, Angsuran, dan Kas (hanya membaca data).
 *
 * Memastikan setiap angka di kartu piutang, laporan pinjaman, buku kas, dan neraca dapat
 * ditelusuri ke transaksi asalnya: pencairan ↔ kas keluar, angsuran ↔ kas masuk,
 * status jadwal ↔ pembayaran, dan status pinjaman ↔ jadwal.
 *
 * Contoh: php artisan sipintar:audit-pinjaman
 */
class AuditPinjamanKas extends Command
{
    protected $signature = 'sipintar:audit-pinjaman';

    protected $description = 'Audit konsistensi data pinjaman, angsuran, dan kas (read-only)';

    public function handle(): int
    {
        $angsuranSah = fn () => DB::table('tblANGSURAN as a')
            ->leftJoin('tblKOREKSI_ANGSURAN as k', 'a.angsuran_id', '=', 'k.angsuran_id')
            ->whereNull('k.koreksi_id');

        $cek = [
            'Pencairan tanpa mutasi kas keluar' => DB::table('tblPENCAIRAN as p')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tblMUTASI_KAS as m')
                    ->whereColumn('m.pencairan_id', 'p.pencairan_id')->where('m.arah', 'keluar'))
                ->count(),

            'Pinjaman cair/lunas tanpa data pencairan' => DB::table('tblPINJAMAN as p')
                ->whereIn('p.status_pinjaman', ['cair', 'lunas'])
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tblPENCAIRAN as c')->whereColumn('c.pinjaman_id', 'p.pinjaman_id'))
                ->count(),

            'Pinjaman cair/lunas tanpa jadwal angsuran' => DB::table('tblPINJAMAN as p')
                ->whereIn('p.status_pinjaman', ['cair', 'lunas'])
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tblJADWAL_ANGSURAN as j')->whereColumn('j.pinjaman_id', 'p.pinjaman_id'))
                ->count(),

            'Angsuran langsung tanpa mutasi kas masuk' => $angsuranSah()
                ->whereNull('a.detail_potongan_id')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tblMUTASI_KAS as m')
                    ->whereColumn('m.angsuran_id', 'a.angsuran_id')->where('m.arah', 'masuk'))
                ->count(),

            'Paket potongan selesai tanpa mutasi kas penerimaan' => DB::table('tblRENCANA_POTONGAN as r')
                ->where('r.status_potongan', 'selesai')
                ->where('r.total_realisasi', '>', 0)
                ->get(['r.rencana_potongan_id'])
                ->filter(fn ($r) => ! DB::table('tblMUTASI_KAS')->where('arah', 'masuk')
                    ->where('uraian', 'like', '%#RP-' . $r->rencana_potongan_id . ' %')->exists())
                ->count(),

            'Jadwal LUNAS tanpa angsuran sah' => DB::table('tblJADWAL_ANGSURAN as j')
                ->where('j.status_bayar', 'lunas')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tblANGSURAN as a')
                    ->leftJoin('tblKOREKSI_ANGSURAN as k', 'a.angsuran_id', '=', 'k.angsuran_id')
                    ->whereColumn('a.jadwal_angsuran_id', 'j.jadwal_angsuran_id')->whereNull('k.koreksi_id'))
                ->count(),

            'Jadwal BELUM padahal punya angsuran sah' => DB::table('tblJADWAL_ANGSURAN as j')
                ->where('j.status_bayar', 'belum')
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('tblANGSURAN as a')
                    ->leftJoin('tblKOREKSI_ANGSURAN as k', 'a.angsuran_id', '=', 'k.angsuran_id')
                    ->whereColumn('a.jadwal_angsuran_id', 'j.jadwal_angsuran_id')->whereNull('k.koreksi_id'))
                ->count(),

            'Jadwal dibayar lebih dari sekali (pembayaran ganda)' => $angsuranSah()
                ->select('a.jadwal_angsuran_id')->groupBy('a.jadwal_angsuran_id')
                ->havingRaw('COUNT(*) > 1')->get()->count(),

            'Pinjaman berstatus CAIR padahal semua jadwal lunas' => DB::table('tblPINJAMAN as p')
                ->where('p.status_pinjaman', 'cair')
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('tblJADWAL_ANGSURAN as j')->whereColumn('j.pinjaman_id', 'p.pinjaman_id'))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tblJADWAL_ANGSURAN as j')
                    ->whereColumn('j.pinjaman_id', 'p.pinjaman_id')->where('j.status_bayar', '!=', 'lunas'))
                ->count(),

            'Pinjaman berstatus LUNAS padahal masih ada jadwal belum' => DB::table('tblPINJAMAN as p')
                ->where('p.status_pinjaman', 'lunas')
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('tblJADWAL_ANGSURAN as j')
                    ->whereColumn('j.pinjaman_id', 'p.pinjaman_id')->where('j.status_bayar', '!=', 'lunas'))
                ->count(),

            'Pokok terbayar melebihi plafon pinjaman' => DB::table('tblPINJAMAN as p')->get(['p.pinjaman_id', 'p.nominal_pinjaman'])
                ->filter(fn ($p) => (float) $angsuranSah()
                    ->join('tblJADWAL_ANGSURAN as j', 'a.jadwal_angsuran_id', '=', 'j.jadwal_angsuran_id')
                    ->where('j.pinjaman_id', $p->pinjaman_id)->sum('a.bayar_pokok') > (float) $p->nominal_pinjaman)
                ->count(),

            'Nasabah dengan lebih dari satu pinjaman berjalan' => DB::table('tblPINJAMAN')
                ->whereIn('status_pinjaman', ['diajukan', 'layak', 'cair'])
                ->select('nasabah_id')->groupBy('nasabah_id')->havingRaw('COUNT(*) > 1')->get()->count(),

            'Pinjaman milik nasabah non-pegawai' => DB::table('tblPINJAMAN as p')
                ->join('tblNASABAH as n', 'p.nasabah_id', '=', 'n.nasabah_id')
                ->where('n.jenis_nasabah', '!=', 'pegawai')->count(),

            'Mutasi kas yang tanggalnya di luar periode_id-nya' => DB::table('tblMUTASI_KAS as m')
                ->join('tblPERIODE as p', 'm.periode_id', '=', 'p.periode_id')
                ->get(['m.tgl_mutasi', 'p.tahun', 'p.bulan'])
                ->filter(fn ($m) => (int) substr($m->tgl_mutasi, 0, 4) !== (int) $m->tahun || (int) substr($m->tgl_mutasi, 5, 2) !== (int) $m->bulan)
                ->count(),
        ];

        $baris = [];
        $masalah = 0;
        foreach ($cek as $label => $jumlah) {
            $baris[] = [$label, $jumlah, $jumlah === 0 ? 'OK' : 'PERIKSA'];
            $masalah += $jumlah > 0 ? 1 : 0;
        }
        $this->table(['Pemeriksaan', 'Jumlah temuan', 'Status'], $baris);

        $this->newLine();
        $this->info('Posisi kas (saldo saat ini dan penjumlahan mutasi tercatat):');
        $kas = DB::table('tblKAS')->orderBy('kas_id')->get()->map(function ($k) {
            $neto = (float) DB::table('tblMUTASI_KAS')->where('kas_id', $k->kas_id)
                ->sum(DB::raw("CASE WHEN arah = 'masuk' THEN nominal ELSE -nominal END"));

            return [$k->nama_kas, $this->rp($k->saldo), $this->rp($neto), $this->rp((float) $k->saldo - $neto)];
        });
        $this->table(['Kas', 'Saldo saat ini', 'Neto mutasi', 'Saldo awal tersirat'], $kas->all());

        if ($masalah === 0) {
            $this->info('Semua pemeriksaan konsisten.');

            return self::SUCCESS;
        }

        $this->warn("{$masalah} pemeriksaan perlu ditelusuri.");

        return self::FAILURE;
    }

    private function rp(float $n): string
    {
        return 'Rp ' . number_format($n, 0, ',', '.');
    }
}
