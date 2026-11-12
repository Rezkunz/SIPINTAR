<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tutup buku bulanan oleh Manager.
 *
 * Periode yang ditutup (tblPERIODE.status_periode = 'tutup') dikunci oleh PeriodeLockService,
 * sehingga seluruh transaksi bertanggal di bulan itu ditolak. Saat ditutup, saldo awal dan akhir
 * setiap kas dibekukan ke tblSALDO_KAS sebagai dasar laporan bulan tersebut.
 */
class TutupPeriodeService
{
    /**
     * Daftar bulan dari periode tercatat paling awal sampai bulan berjalan, terbaru di atas.
     */
    public function daftarPeriode(): Collection
    {
        $tercatat = DB::table('tblPERIODE as p')
            ->leftJoin('tblUSER as u', 'p.user_id', '=', 'u.user_id')
            ->get(['p.*', 'u.nama_user'])
            ->keyBy(fn ($p) => sprintf('%04d-%02d', $p->tahun, $p->bulan));

        $bulanIni = now()->startOfMonth();
        // Mulai dari bulan paling awal yang tercatat (atau bulan berjalan bila belum ada periode)
        $awal = $tercatat->isEmpty()
            ? $bulanIni->copy()
            : Carbon::createFromFormat('Y-m-d', $tercatat->keys()->sort()->first() . '-01')->startOfMonth();
        if ($awal->gt($bulanIni)) {
            $awal = $bulanIni->copy();
        }

        $daftar = collect();
        for ($bln = $bulanIni->copy(); $bln->gte($awal); $bln->subMonth()) {
            $kunci = $bln->format('Y-m');
            $p = $tercatat->get($kunci);

            $daftar->push((object) [
                'kunci'       => $kunci,
                'tahun'       => $bln->year,
                'bulan'       => $bln->month,
                'awal_bulan'  => $bln->copy(),
                'periode_id'  => $p->periode_id ?? null,
                'status'      => $p->status_periode ?? 'buka',
                'tgl_tutup'   => isset($p->tgl_tutup) ? Carbon::parse($p->tgl_tutup) : null,
                'ditutup_oleh'=> ($p->status_periode ?? null) === 'tutup' ? ($p->nama_user ?? null) : null,
                'berjalan'    => $bln->equalTo($bulanIni),
            ]);
        }

        return $daftar;
    }

    /**
     * Ringkasan aktivitas satu bulan: tabungan, kas, dan pinjaman.
     */
    public function ringkasan(Carbon $awalBulan): array
    {
        $awal  = $awalBulan->copy()->startOfMonth();
        $akhir = $awalBulan->copy()->endOfMonth();

        $tabungan = DB::table('tblTRANSAKSI')
            ->whereDate('tgl_transaksi', '>=', $awal)
            ->whereDate('tgl_transaksi', '<=', $akhir)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(kredit), 0) as setoran, COALESCE(SUM(debit), 0) as penarikan')
            ->first();

        $pencairan = DB::table('tblPENCAIRAN')
            ->whereDate('tgl_cair', '>=', $awal)
            ->whereDate('tgl_cair', '<=', $akhir)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(nominal_cair), 0) as total')
            ->first();

        // Angsuran sah = belum dikoreksi (sama dengan Rekap Pinjaman & Neraca)
        $angsuran = DB::table('tblANGSURAN as ang')
            ->leftJoin('tblKOREKSI_ANGSURAN as kor', 'ang.angsuran_id', '=', 'kor.angsuran_id')
            ->whereNull('kor.koreksi_id')
            ->whereDate('ang.tgl_bayar', '>=', $awal)
            ->whereDate('ang.tgl_bayar', '<=', $akhir)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(ang.bayar_pokok), 0) as pokok, COALESCE(SUM(ang.bayar_bunga), 0) as bunga')
            ->first();

        return [
            'tabungan'  => $tabungan,
            'pencairan' => $pencairan,
            'angsuran'  => $angsuran,
            'kas'       => $this->posisiKas($awal),
        ];
    }

    /**
     * Posisi tiap kas di bulan tersebut. Bila periode sudah ditutup, pakai angka yang dibekukan
     * di tblSALDO_KAS; bila belum, hitung dari saldo kas saat ini dan mutasi kas.
     */
    public function posisiKas(Carbon $awalBulan): Collection
    {
        $awal  = $awalBulan->copy()->startOfMonth();
        $akhir = $awalBulan->copy()->endOfMonth();

        $periode = $this->cariPeriode($awal);
        $beku = $periode && $periode->status_periode === 'tutup'
            ? DB::table('tblSALDO_KAS')->where('periode_id', $periode->periode_id)->get()->keyBy('kas_id')
            : collect();

        return DB::table('tblKAS')->orderBy('kas_id')->get()->map(function ($kas) use ($awal, $akhir, $beku) {
            $mutasi = DB::table('tblMUTASI_KAS')
                ->where('kas_id', $kas->kas_id)
                ->whereDate('tgl_mutasi', '>=', $awal)
                ->whereDate('tgl_mutasi', '<=', $akhir)
                ->selectRaw("COALESCE(SUM(CASE WHEN arah = 'masuk' THEN nominal ELSE 0 END), 0) as masuk")
                ->selectRaw("COALESCE(SUM(CASE WHEN arah = 'keluar' THEN nominal ELSE 0 END), 0) as keluar")
                ->first();

            $simpan = $beku->get($kas->kas_id);

            return (object) [
                'kas_id'      => $kas->kas_id,
                'nama_kas'    => $kas->nama_kas,
                'jenis_kas'   => $kas->jenis_kas,
                'saldo_awal'  => $simpan ? (float) $simpan->saldo_awal : $this->saldoKasPada($kas->kas_id, $awal->copy()->subDay()),
                'masuk'       => (float) $mutasi->masuk,
                'keluar'      => (float) $mutasi->keluar,
                'saldo_akhir' => $simpan ? (float) $simpan->saldo_akhir : $this->saldoKasPada($kas->kas_id, $akhir),
                'dibekukan'   => (bool) $simpan,
            ];
        });
    }

    /**
     * Daftar periksa sebelum tutup buku.
     * 'penghalang' wajib beres sebelum periode dapat ditutup; 'peringatan' hanya pengingat.
     */
    public function periksa(Carbon $awalBulan): array
    {
        $awal  = $awalBulan->copy()->startOfMonth();
        $akhir = $awalBulan->copy()->endOfMonth();
        $periode = $this->cariPeriode($awal);

        $penghalang = [];
        $peringatan = [];

        if ($periode && $periode->status_periode === 'tutup') {
            $penghalang[] = 'Periode ini sudah ditutup.';
        }

        if ($awal->gte(now()->startOfMonth())) {
            $penghalang[] = 'Bulan ' . $awal->translatedFormat('F Y') . ' belum berakhir. Tutup buku baru dapat dilakukan mulai '
                . $awal->copy()->addMonth()->translatedFormat('d F Y') . '.';
        }

        $belumDitutup = DB::table('tblPERIODE')
            ->where('status_periode', 'buka')
            ->where(fn ($q) => $q->where('tahun', '<', $awal->year)
                ->orWhere(fn ($q) => $q->where('tahun', $awal->year)->where('bulan', '<', $awal->month)))
            ->orderBy('tahun')->orderBy('bulan')
            ->get();
        if ($belumDitutup->isNotEmpty()) {
            $daftar = $belumDitutup->map(fn ($p) => Carbon::create($p->tahun, $p->bulan, 1)->translatedFormat('F Y'))->implode(', ');
            $penghalang[] = "Periode sebelumnya masih terbuka: {$daftar}. Tutup buku harus berurutan dari bulan paling lama.";
        }

        $draftPending = DB::table('tblDRAFT')
            ->where('status_verifikasi', 'pending')
            ->whereDate('tgl_input', '>=', $awal)
            ->whereDate('tgl_input', '<=', $akhir)
            ->count();
        if ($draftPending > 0) {
            $penghalang[] = "{$draftPending} draft transaksi tabungan dari Siswa Praktik belum diverifikasi Teller.";
        }

        if ($periode) {
            $potonganTerbuka = DB::table('tblRENCANA_POTONGAN')
                ->where('periode_id', $periode->periode_id)
                ->where('status_potongan', '!=', 'selesai')
                ->count();
            if ($potonganTerbuka > 0) {
                $peringatan[] = "{$potonganTerbuka} rencana potongan gaji periode ini belum selesai dibukukan. Penerimaan dananya nanti harus dicatat dengan tanggal di periode yang masih terbuka.";
            }
        }

        $pinjamanMenunggu = DB::table('tblPINJAMAN')
            ->whereIn('status_pinjaman', ['diajukan', 'layak'])
            ->whereDate('tgl_pengajuan', '<=', $akhir)
            ->count();
        if ($pinjamanMenunggu > 0) {
            $peringatan[] = "{$pinjamanMenunggu} pengajuan pinjaman masih menunggu penilaian atau pencairan.";
        }

        return [
            'penghalang' => $penghalang,
            'peringatan' => $peringatan,
            'bisa_tutup' => empty($penghalang),
        ];
    }

    /**
     * Tutup buku: bekukan saldo kas ke tblSALDO_KAS lalu kunci periode.
     *
     * @throws RuntimeException bila masih ada penghalang
     */
    public function tutup(Carbon $awalBulan, ?string $catatan = null): object
    {
        $awal = $awalBulan->copy()->startOfMonth();

        return DB::transaction(function () use ($awal, $catatan) {
            $periode = $this->cariPeriode($awal, kunci: true) ?? $this->buatPeriode($awal);

            $hasil = $this->periksa($awal);
            if (!$hasil['bisa_tutup']) {
                throw new RuntimeException(implode(' ', $hasil['penghalang']));
            }

            $snapshot = [];
            foreach ($this->posisiKas($awal) as $kas) {
                DB::table('tblSALDO_KAS')->updateOrInsert(
                    ['periode_id' => $periode->periode_id, 'kas_id' => $kas->kas_id],
                    ['saldo_awal' => $kas->saldo_awal, 'saldo_akhir' => $kas->saldo_akhir]
                );
                $snapshot[$kas->nama_kas] = ['saldo_awal' => $kas->saldo_awal, 'saldo_akhir' => $kas->saldo_akhir];
            }

            DB::table('tblPERIODE')->where('periode_id', $periode->periode_id)->update([
                'status_periode' => 'tutup',
                'tgl_tutup'      => now(),
                'user_id'        => auth()->id(),
            ]);

            // Periode berikutnya disiapkan terbuka agar transaksi bulan berikutnya tetap berjalan
            $berikut = $awal->copy()->addMonth();
            if (!$this->cariPeriode($berikut)) {
                $this->buatPeriode($berikut);
            }

            AuditLogService::log(
                aksi: 'TUTUP_PERIODE',
                entitas: 'tblPERIODE',
                entitasId: $periode->periode_id,
                dataLama: ['status_periode' => $periode->status_periode],
                dataBaru: array_filter([
                    'status_periode' => 'tutup',
                    'periode'        => $awal->format('Y-m'),
                    'saldo_kas'      => $snapshot,
                    'catatan'        => $catatan,
                ])
            );

            return DB::table('tblPERIODE')->where('periode_id', $periode->periode_id)->first();
        });
    }

    /**
     * Buka kembali periode yang sudah ditutup (hanya periode tertutup paling akhir) dengan alasan tertulis.
     *
     * @throws RuntimeException
     */
    public function bukaKembali(Carbon $awalBulan, string $alasan): object
    {
        $awal = $awalBulan->copy()->startOfMonth();

        return DB::transaction(function () use ($awal, $alasan) {
            $periode = $this->cariPeriode($awal, kunci: true);
            if (!$periode || $periode->status_periode !== 'tutup') {
                throw new RuntimeException('Periode ' . $awal->translatedFormat('F Y') . ' tidak dalam keadaan tertutup.');
            }

            $tutupSesudahnya = DB::table('tblPERIODE')
                ->where('status_periode', 'tutup')
                ->where(fn ($q) => $q->where('tahun', '>', $awal->year)
                    ->orWhere(fn ($q) => $q->where('tahun', $awal->year)->where('bulan', '>', $awal->month)))
                ->exists();
            if ($tutupSesudahnya) {
                throw new RuntimeException('Hanya periode tertutup paling akhir yang dapat dibuka kembali. Buka dulu periode sesudahnya.');
            }

            DB::table('tblPERIODE')->where('periode_id', $periode->periode_id)->update([
                'status_periode' => 'buka',
                'tgl_tutup'      => null,
            ]);

            AuditLogService::log(
                aksi: 'BUKA_PERIODE',
                entitas: 'tblPERIODE',
                entitasId: $periode->periode_id,
                dataLama: ['status_periode' => 'tutup', 'tgl_tutup' => $periode->tgl_tutup, 'ditutup_oleh' => $periode->user_id],
                dataBaru: ['status_periode' => 'buka', 'periode' => $awal->format('Y-m'), 'alasan' => $alasan]
            );

            return DB::table('tblPERIODE')->where('periode_id', $periode->periode_id)->first();
        });
    }

    /**
     * Riwayat tutup/buka periode dari audit log.
     */
    public function riwayat(int $batas = 10): Collection
    {
        return DB::table('tblAUDIT_LOG as a')
            ->leftJoin('tblUSER as u', 'a.user_id', '=', 'u.user_id')
            ->where('a.entitas', 'tblPERIODE')
            ->whereIn('a.aksi', ['TUTUP_PERIODE', 'BUKA_PERIODE'])
            ->orderByDesc('a.created_at')
            ->orderByDesc('a.audit_log_id')
            ->limit($batas)
            ->get(['a.*', 'u.nama_user'])
            ->map(function ($log) {
                $log->data = json_decode($log->data_baru ?? '{}', true) ?: [];
                return $log;
            });
    }

    /**
     * Saldo satu kas pada akhir tanggal = saldo saat ini dikurangi mutasi sesudah tanggal tersebut
     * (rumus yang sama dengan Neraca dan Buku Kas Bendahara).
     */
    public function saldoKasPada(int $kasId, Carbon $tanggal): float
    {
        $saldoSekarang = (float) DB::table('tblKAS')->where('kas_id', $kasId)->value('saldo');
        $mutasiSesudah = (float) DB::table('tblMUTASI_KAS')
            ->where('kas_id', $kasId)
            ->whereDate('tgl_mutasi', '>', $tanggal)
            ->sum(DB::raw("CASE WHEN arah = 'masuk' THEN nominal ELSE -nominal END"));

        return $saldoSekarang - $mutasiSesudah;
    }

    private function cariPeriode(Carbon $awalBulan, bool $kunci = false): ?object
    {
        $query = DB::table('tblPERIODE')->where('tahun', $awalBulan->year)->where('bulan', $awalBulan->month);

        return ($kunci ? $query->lockForUpdate() : $query)->first();
    }

    private function buatPeriode(Carbon $awalBulan): object
    {
        $id = DB::table('tblPERIODE')->insertGetId([
            'user_id'        => null,
            'tahun'          => $awalBulan->year,
            'bulan'          => $awalBulan->month,
            'status_periode' => 'buka',
            'tgl_tutup'      => null,
        ], 'periode_id');

        return DB::table('tblPERIODE')->where('periode_id', $id)->first();
    }
}
