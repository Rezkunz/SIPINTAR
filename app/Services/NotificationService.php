<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class NotificationService
{
    public const CACHE_TTL_SECONDS = 30;

    /**
     * Ambil daftar notifikasi terurut & terisolasi per user dan peran.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function untuk(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $cacheKey = self::cacheKey($user->user_id);

        $items = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($user) {
            $raw = match ($user->role) {
                'teller' => self::untukTeller(),
                'siswa_praktik' => self::untukSiswaPraktik($user),
                'nasabah' => self::untukNasabah($user),
                'bendahara_mini', 'bendahara' => self::adaptPinjaman($user),
                'manager' => self::untukManager(),
                default => [],
            };

            return self::sortItems($raw);
        });

        return self::applyReadState($items, $user->user_id);
    }

    /**
     * Hitung jumlah notifikasi "Perlu Tindakan" yang belum dibaca untuk badge lonceng.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function countActionUnread(array $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (($item['jenis'] ?? 'info') === 'action' && empty($item['dibaca'])) {
                $count++;
            }
        }

        return $count;
    }

    public static function cacheKey(int|string $userId): string
    {
        return "notif_u_{$userId}";
    }

    public static function forgetCache(int|string $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    /**
     * Tandai seluruh notifikasi saat ini sebagai sudah dibaca di session.
     */
    public static function markAllReadInSession(User $user): void
    {
        $items = self::untuk($user);
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (array $item) => $item['id'] ?? null,
            $items
        ))));

        $sessionKeyIds = "notif_read_ids_{$user->user_id}";
        $sessionKeyTime = "notif_read_at_{$user->user_id}";

        $existing = session($sessionKeyIds, []);
        if (! is_array($existing)) {
            $existing = [];
        }

        $merged = array_slice(array_values(array_unique(array_merge($existing, $ids))), -200);

        session([
            $sessionKeyIds => $merged,
            $sessionKeyTime => now()->toIso8601String(),
        ]);

        self::forgetCache($user->user_id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function untukTeller(): array
    {
        $items = [];

        // 1. Draft rekening/transaksi pending menunggu verifikasi
        $pendingDraft = DB::table('tblDRAFT')
            ->where('status_verifikasi', 'pending')
            ->count();

        if ($pendingDraft > 0) {
            $items[] = self::makeItem(
                id: "teller_draft_pending_{$pendingDraft}",
                jenis: 'action',
                judul: 'Verifikasi Draft Transaksi',
                teks: "{$pendingDraft} draft transaksi dari Siswa Praktik menunggu verifikasi Anda.",
                link: self::safeRoute('tabungan.verifikasi'),
                waktu: now()->toIso8601String(),
                prioritas: 1,
                warna: 'amber',
                label: 'Perlu Tindakan',
            );
        }

        // 2. Kas tunai menipis
        $minSaldo = (float) config('sipintar.kas.min_saldo', 500000);
        $kas = DB::table('tblKAS')->where('jenis_kas', 'kas')->first();

        if ($kas && (float) $kas->saldo < $minSaldo) {
            $saldoFormatted = number_format((float) $kas->saldo, 0, ',', '.');
            $minFormatted = number_format($minSaldo, 0, ',', '.');
            $items[] = self::makeItem(
                id: 'teller_kas_menipis_' . (int) floor((float) $kas->saldo),
                jenis: 'action',
                judul: 'Saldo Kas Tunai Menipis',
                teks: "Saldo kas tunai saat ini Rp {$saldoFormatted}, di bawah batas minimum Rp {$minFormatted}.",
                link: self::safeRoute('tabungan.mutasi'),
                waktu: now()->toIso8601String(),
                prioritas: 1,
                warna: 'red',
                label: 'Mendesak',
            );
        }

        // 3. Pengingat hitung jasa tabungan akhir bulan (H-2 s.d. akhir bulan jika belum diposting)
        $now = now();
        if ($now->day >= ($now->daysInMonth - 2)) {
            $sudahHitungJasa = DB::table('tblTRANSAKSI as t')
                ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
                ->where('jt.nama_jenis', 'jasa_tabungan')
                ->whereYear('t.tgl_transaksi', $now->year)
                ->whereMonth('t.tgl_transaksi', $now->month)
                ->exists();

            if (! $sudahHitungJasa) {
                $items[] = self::makeItem(
                    id: 'teller_jasa_tabungan_' . $now->format('Y_m'),
                    jenis: 'action',
                    judul: 'Hitung Jasa Tabungan Akhir Bulan',
                    teks: 'Periode ' . $now->format('m/Y') . ' segera berakhir. Segera proses perhitungan jasa tabungan.',
                    link: self::safeRoute('tabungan.jasa'),
                    waktu: $now->toIso8601String(),
                    prioritas: 2,
                    warna: 'amber',
                    label: 'Pengingat',
                );
            }
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function untukSiswaPraktik(User $user): array
    {
        $items = [];

        // 1. Rekening pending menunggu setoran awal Rp 20.000
        $pendingRek = DB::table('tblREKENING')
            ->where('status_rekening', 'pending')
            ->count();

        if ($pendingRek > 0) {
            $items[] = self::makeItem(
                id: "siswa_rek_pending_{$pendingRek}",
                jenis: 'action',
                judul: 'Rekening Menunggu Setoran Awal',
                teks: "{$pendingRek} rekening berstatus pending menunggu setoran awal minimal Rp 20.000.",
                link: self::safeRoute('tabungan.buka-rekening'),
                waktu: now()->toIso8601String(),
                prioritas: 1,
                warna: 'amber',
                label: 'Perlu Tindakan',
            );
        }

        // 2. Draft milik siswa praktik ini yang sudah diverifikasi (7 hari terakhir)
        $batas = now()->subDays(7);
        $disetujui = DB::table('tblDRAFT')
            ->where('siswa_id', $user->user_id)
            ->where('status_verifikasi', 'disetujui')
            ->where('tgl_input', '>=', $batas)
            ->selectRaw('COUNT(*) as total, MAX(tgl_input) as terbaru')
            ->first();

        if ($disetujui && (int) $disetujui->total > 0) {
            $total = (int) $disetujui->total;
            $items[] = self::makeItem(
                id: "siswa_draft_disetujui_{$user->user_id}_{$total}_" . Carbon::parse($disetujui->terbaru)->timestamp,
                jenis: 'info',
                judul: 'Draft Transaksi Disetujui',
                teks: "{$total} draft transaksi Anda dalam 7 hari terakhir telah disetujui Teller.",
                link: self::safeRoute('tabungan.draft.riwayat'),
                waktu: Carbon::parse($disetujui->terbaru)->toIso8601String(),
                prioritas: 3,
                warna: 'green',
                label: 'Informasi',
            );
        }

        $ditolak = DB::table('tblDRAFT')
            ->where('siswa_id', $user->user_id)
            ->where('status_verifikasi', 'ditolak')
            ->where('tgl_input', '>=', $batas)
            ->selectRaw('COUNT(*) as total, MAX(tgl_input) as terbaru')
            ->first();

        if ($ditolak && (int) $ditolak->total > 0) {
            $total = (int) $ditolak->total;
            $items[] = self::makeItem(
                id: "siswa_draft_ditolak_{$user->user_id}_{$total}_" . Carbon::parse($ditolak->terbaru)->timestamp,
                jenis: 'info',
                judul: 'Draft Transaksi Ditolak',
                teks: "{$total} draft transaksi Anda dalam 7 hari terakhir ditolak oleh Teller.",
                link: self::safeRoute('tabungan.draft.riwayat'),
                waktu: Carbon::parse($ditolak->terbaru)->toIso8601String(),
                prioritas: 2,
                warna: 'red',
                label: 'Ditolak',
            );
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function untukNasabah(User $user): array
    {
        $items = [];

        if ($user->nasabah_id) {
            // 1. Rekening belum setor awal
            $pendingRek = DB::table('tblREKENING')
                ->where('nasabah_id', $user->nasabah_id)
                ->where('status_rekening', 'pending')
                ->count();

            if ($pendingRek > 0) {
                $items[] = self::makeItem(
                    id: "nasabah_rek_pending_{$user->nasabah_id}_{$pendingRek}",
                    jenis: 'action',
                    judul: 'Rekening Menunggu Setoran Awal',
                    teks: "Anda memiliki {$pendingRek} rekening yang menunggu setoran awal Rp 20.000 agar aktif.",
                    link: self::safeRoute('nasabah.saldo'),
                    waktu: now()->toIso8601String(),
                    prioritas: 1,
                    warna: 'amber',
                    label: 'Perlu Tindakan',
                );
            }

            // 2. Transaksi dikoreksi (1 notifikasi per proses koreksi, bukan per baris)
            $koreksiLogs = DB::table('tblAUDIT_LOG as a')
                ->join('tblTRANSAKSI as t', 'a.entitas_id', '=', 't.transaksi_id')
                ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
                ->where('a.aksi', 'KOREKSI_TRANSAKSI')
                ->where('a.entitas', 'tblTRANSAKSI')
                ->where('r.nasabah_id', $user->nasabah_id)
                ->where('a.created_at', '>=', now()->subDays(14))
                ->orderByDesc('a.audit_log_id')
                ->limit(5)
                ->select('a.audit_log_id', 'a.data_baru', 'a.created_at', 'r.no_rek')
                ->get();

            $excludedTrxIds = [];
            foreach ($koreksiLogs as $log) {
                $payload = json_decode((string) $log->data_baru, true) ?: [];
                if (! empty($payload['reversal_id'])) {
                    $excludedTrxIds[] = (int) $payload['reversal_id'];
                }
                if (! empty($payload['corrected_id'])) {
                    $excludedTrxIds[] = (int) $payload['corrected_id'];
                }

                $reversalLabel = $payload['reversal_label'] ?? 'Pembatalan Setoran/Penarikan';
                $correctedLabel = $payload['corrected_label'] ?? 'Hasil Koreksi';
                $nominal = number_format((float) ($payload['nominal'] ?? 0), 0, ',', '.');

                $items[] = self::makeItem(
                    id: "nasabah_koreksi_{$log->audit_log_id}",
                    jenis: 'info',
                    judul: 'Transaksi Dikoreksi Teller',
                    teks: "Rekening {$log->no_rek}: {$reversalLabel} & {$correctedLabel} menjadi Rp {$nominal}.",
                    link: self::safeRoute('nasabah.saldo'),
                    waktu: Carbon::parse($log->created_at)->toIso8601String(),
                    prioritas: 2,
                    warna: 'blue',
                    label: 'Koreksi',
                );
            }

            // 3. Transaksi baru (setoran/penarikan) 7 hari terakhir, mengecualikan baris koreksi
            $trxQuery = DB::table('tblTRANSAKSI as t')
                ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
                ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
                ->where('r.nasabah_id', $user->nasabah_id)
                ->where('t.tgl_transaksi', '>=', now()->subDays(7))
                ->whereIn('jt.nama_jenis', ['setoran', 'penarikan']);

            if ($excludedTrxIds !== []) {
                $trxQuery->whereNotIn('t.transaksi_id', $excludedTrxIds);
            }

            $recentTrx = $trxQuery
                ->orderByDesc('t.transaksi_id')
                ->limit(3)
                ->select('t.transaksi_id', 't.debit', 't.kredit', 't.tgl_transaksi', 'r.no_rek', 'jt.nama_jenis')
                ->get();

            foreach ($recentTrx as $trx) {
                $nominalVal = max((float) $trx->debit, (float) $trx->kredit);
                $nominal = number_format($nominalVal, 0, ',', '.');
                $jenisLabel = ucfirst((string) $trx->nama_jenis);

                $items[] = self::makeItem(
                    id: "nasabah_trx_{$trx->transaksi_id}",
                    jenis: 'info',
                    judul: "{$jenisLabel} Tabungan",
                    teks: "Transaksi {$trx->nama_jenis} sebesar Rp {$nominal} pada rekening {$trx->no_rek}.",
                    link: self::safeRoute('nasabah.saldo'),
                    waktu: Carbon::parse($trx->tgl_transaksi)->toIso8601String(),
                    prioritas: 3,
                    warna: $trx->nama_jenis === 'setoran' ? 'green' : 'amber',
                    label: 'Informasi',
                );
            }
        }

        // 4. Notifikasi pinjaman untuk pegawai (jika ada)
        foreach (self::adaptPinjaman($user) as $pinjamanItem) {
            $items[] = $pinjamanItem;
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function untukManager(): array
    {
        $items = [];
        $now = now();

        // 1. Periode bulan lalu belum ditutup
        $unclosedPast = DB::table('tblPERIODE')
            ->where('status_periode', 'buka')
            ->where(function ($q) use ($now) {
                $q->where('tahun', '<', $now->year)
                    ->orWhere(function ($q2) use ($now) {
                        $q2->where('tahun', $now->year)
                            ->where('bulan', '<', $now->month);
                    });
            })
            ->count();

        if ($unclosedPast > 0) {
            $items[] = self::makeItem(
                id: "manager_periode_buka_{$unclosedPast}",
                jenis: 'action',
                judul: 'Periode Bulan Lalu Belum Ditutup',
                teks: "{$unclosedPast} periode akuntansi sebelumnya masih berstatus terbuka dan perlu ditutup.",
                link: self::safeRoute('periode.tutup'),
                waktu: $now->toIso8601String(),
                prioritas: 1,
                warna: 'amber',
                label: 'Perlu Tindakan',
            );
        }

        // 2. Selisih saldo rekening vs saldo_akhir transaksi terakhir
        $latestTrxSub = DB::table('tblTRANSAKSI')
            ->selectRaw('rekening_id, MAX(transaksi_id) as max_id')
            ->groupBy('rekening_id');

        $desyncCount = DB::table('tblREKENING as r')
            ->joinSub($latestTrxSub, 'lt', fn ($join) => $join->on('r.rekening_id', '=', 'lt.rekening_id'))
            ->join('tblTRANSAKSI as t', 'lt.max_id', '=', 't.transaksi_id')
            ->whereRaw('ABS(r.saldo - t.saldo_akhir) > 0.01')
            ->count();

        if ($desyncCount > 0) {
            $items[] = self::makeItem(
                id: "manager_saldo_desync_{$desyncCount}",
                jenis: 'action',
                judul: 'Selisih Saldo Rekening Terdeteksi',
                teks: "{$desyncCount} rekening memiliki saldo yang tidak sinkron dengan mutasi transaksi terakhir.",
                link: self::safeRoute('laporan.tabungan'),
                waktu: $now->toIso8601String(),
                prioritas: 1,
                warna: 'red',
                label: 'Mendesak',
            );
        }

        // 3. Ringkasan antrean tertunda lintas modul (info)
        $draftPending = DB::table('tblDRAFT')->where('status_verifikasi', 'pending')->count();
        $pinjamanPending = DB::table('tblPINJAMAN')->whereIn('status_pinjaman', ['diajukan', 'layak'])->count();
        $totalAntrean = $draftPending + $pinjamanPending;

        if ($totalAntrean > 0) {
            $items[] = self::makeItem(
                id: "manager_antrean_{$draftPending}_{$pinjamanPending}",
                jenis: 'info',
                judul: 'Ringkasan Antrean Lintas Modul',
                teks: "Saat ini terdapat {$draftPending} draft tabungan pending dan {$pinjamanPending} pengajuan pinjaman aktif.",
                link: self::safeRoute('dashboard'),
                waktu: $now->toIso8601String(),
                prioritas: 3,
                warna: 'blue',
                label: 'Ringkasan',
            );
        }

        return $items;
    }

    /**
     * Adaptasi output NotifikasiPinjamanService ke format seragam NotificationService.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function adaptPinjaman(User $user): array
    {
        $raw = NotifikasiPinjamanService::untuk($user);
        if (! is_array($raw)) {
            return [];
        }

        $colorMap = [
            'blue' => 'blue',
            'emerald' => 'green',
            'amber' => 'amber',
            'red' => 'red',
        ];

        $items = [];
        foreach ($raw as $entry) {
            $plainText = trim(strip_tags((string) ($entry['pesan'] ?? '')));
            $kode = (string) ($entry['kode'] ?? 'pinjaman');
            $warna = $colorMap[$entry['warna'] ?? 'blue'] ?? 'blue';

            $items[] = self::makeItem(
                id: 'pinjaman_' . $kode . '_' . substr(md5($plainText), 0, 8),
                jenis: 'action',
                judul: (string) ($entry['judul'] ?? 'Notifikasi Pinjaman'),
                teks: $plainText,
                link: (string) ($entry['url'] ?? self::safeRoute('dashboard')),
                waktu: now()->toIso8601String(),
                prioritas: $warna === 'red' ? 1 : 2,
                warna: $warna,
                label: (string) ($entry['label'] ?? 'Perlu Tindakan'),
            );
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private static function makeItem(
        string $id,
        string $jenis,
        string $judul,
        string $teks,
        string $link,
        string $waktu,
        int $prioritas,
        string $warna,
        string $label
    ): array {
        return [
            'id' => $id,
            'jenis' => $jenis,
            'judul' => $judul,
            'teks' => $teks,
            'link' => $link,
            'waktu' => $waktu,
            'prioritas' => $prioritas,
            'warna' => $warna,
            'label' => $label,
            'dibaca' => false,
        ];
    }

    /**
     * Urutkan: Perlu Tindakan ('action') di atas, Informasi ('info') di bawah,
     * lalu prioritas terkecil, lalu waktu terbaru.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function sortItems(array $items): array
    {
        usort($items, static function (array $a, array $b): int {
            $typeA = ($a['jenis'] ?? 'info') === 'action' ? 0 : 1;
            $typeB = ($b['jenis'] ?? 'info') === 'action' ? 0 : 1;
            if ($typeA !== $typeB) {
                return $typeA <=> $typeB;
            }

            $prioA = (int) ($a['prioritas'] ?? 99);
            $prioB = (int) ($b['prioritas'] ?? 99);
            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }

            return strcmp((string) ($b['waktu'] ?? ''), (string) ($a['waktu'] ?? ''));
        });

        return array_values($items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function applyReadState(array $items, int|string $userId): array
    {
        $readIds = session("notif_read_ids_{$userId}", []);
        if (! is_array($readIds)) {
            $readIds = [];
        }

        foreach ($items as &$item) {
            $item['dibaca'] = in_array($item['id'] ?? '', $readIds, true);
        }
        unset($item);

        return $items;
    }

    private static function safeRoute(string $name): string
    {
        return Route::has($name) ? route($name) : url('/dashboard');
    }
}
