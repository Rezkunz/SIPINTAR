<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Notifikasi "tugas tertunda" modul pinjaman, dihitung langsung dari data saat halaman dibuka
 * (tanpa tabel notifikasi), sehingga angka di lonceng selalu sesuai kondisi terkini.
 *
 * Berlaku untuk Bendahara Bank Mini, Bendahara Sekolah, dan Nasabah Pegawai.
 * Mengembalikan null untuk peran lain (lonceng peran tersebut tidak diubah).
 */
class NotifikasiPinjamanService
{
    /**
     * @return array<int, array{kode: string, judul: string, pesan: string, url: string, warna: string, label: string}>|null
     */
    public static function untuk(User $user): ?array
    {
        return match ($user->role) {
            'bendahara_mini' => self::bendaharaBankMini(),
            'bendahara'      => self::bendaharaSekolah(),
            'nasabah'        => self::nasabahPegawai($user),
            default          => null,
        };
    }

    private static function bendaharaBankMini(): array
    {
        $item = [];
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

        $diajukan = DB::table('tblPINJAMAN')->where('status_pinjaman', 'diajukan')->count();
        if ($diajukan) {
            $item[] = self::item('pengajuan-baru', 'Penilaian Kelayakan', "<strong>{$diajukan} pengajuan pinjaman</strong> menunggu penilaian kelayakan.", route('bbm.pinjaman.kelayakan'), 'blue', 'Perlu dinilai');
        }

        $layak = DB::table('tblPINJAMAN')->where('status_pinjaman', 'layak');
        if ($jumlah = $layak->count()) {
            $item[] = self::item('menunggu-cair', 'Pencairan Pinjaman', "<strong>{$jumlah} pinjaman layak</strong> menunggu pencairan dana (" . $rp($layak->sum('nominal_pinjaman')) . ').', route('bbm.pinjaman.cair'), 'emerald', 'Siap cair');
        }

        $paket = DB::table('tblRENCANA_POTONGAN')->where('status_potongan', 'menunggu_verifikasi');
        if ($jumlah = $paket->count()) {
            $item[] = self::item('paket-verifikasi', 'Serah Terima Potongan', "<strong>{$jumlah} paket potongan</strong> (" . $rp($paket->sum('total_realisasi')) . ') menunggu konfirmasi penerimaan dana & pembukuan kas.', route('bbm.angsuran.potongan'), 'amber', 'Siap terima');
        }

        // Angsuran potong gaji yang jatuh tempo bulan ini tetapi belum masuk paket potongan aktif
        $belumDiPaket = DB::table('tblJADWAL_ANGSURAN as ja')
            ->join('tblPINJAMAN as p', 'ja.pinjaman_id', '=', 'p.pinjaman_id')
            ->where('p.status_pinjaman', 'cair')->where('p.metode_bayar', 'potong_gaji')
            ->where('ja.status_bayar', 'belum')
            ->whereYear('ja.jatuh_tempo', now()->year)->whereMonth('ja.jatuh_tempo', now()->month)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tblDETAIL_POTONGAN as dp')
                ->whereColumn('dp.jadwal_angsuran_id', 'ja.jadwal_angsuran_id'))
            ->count();
        if ($belumDiPaket) {
            $item[] = self::item('rencana-belum', 'Rencana Potongan', "<strong>{$belumDiPaket} angsuran potong gaji</strong> bulan ini belum disusun dalam rencana potongan.", route('bbm.angsuran.potongan'), 'blue', 'Bulan ini');
        }

        // Potongan gagal dari Bendahara Sekolah yang belum dibayar langsung
        $gagal = DB::table('tblDETAIL_POTONGAN as dp')
            ->join('tblJADWAL_ANGSURAN as ja', 'dp.jadwal_angsuran_id', '=', 'ja.jadwal_angsuran_id')
            ->where('dp.hasil_potongan', 'gagal')->where('ja.status_bayar', 'belum')
            ->distinct()->count('ja.jadwal_angsuran_id');
        if ($gagal) {
            $item[] = self::item('potongan-gagal', 'Potongan Gagal', "<strong>{$gagal} angsuran</strong> gagal dipotong tunjangan. Tagih pembayaran langsung di loket.", route('bbm.angsuran.langsung'), 'red', 'Tagih');
        }

        // Angsuran bayar langsung yang sudah lewat jatuh tempo
        $terlambat = DB::table('tblJADWAL_ANGSURAN as ja')
            ->join('tblPINJAMAN as p', 'ja.pinjaman_id', '=', 'p.pinjaman_id')
            ->where('p.status_pinjaman', 'cair')->where('p.metode_bayar', 'tunai')
            ->where('ja.status_bayar', 'belum')->whereDate('ja.jatuh_tempo', '<', now()->toDateString())
            ->count();
        if ($terlambat) {
            $item[] = self::item('angsuran-terlambat', 'Angsuran Terlambat', "<strong>{$terlambat} angsuran bayar langsung</strong> sudah melewati jatuh tempo.", route('bbm.angsuran.langsung'), 'red', 'Lewat tempo');
        }

        return $item;
    }

    private static function bendaharaSekolah(): array
    {
        $item = [];

        $paketBaru = DB::table('tblRENCANA_POTONGAN')->where('status_potongan', 'dikirim')->count();
        if ($paketBaru) {
            $item[] = self::item('paket-baru', 'Rencana Potongan', "<strong>{$paketBaru} paket potongan</strong> dari Bank Mini menunggu dicatat hasil pemotongannya.", route('bendahara.potongan.index'), 'amber', 'Perlu dicatat');
        }

        $pegawaiAktif = DB::table('tblNASABAH')->where('jenis_nasabah', 'pegawai')->where('status_nasabah', 'aktif')->pluck('nasabah_id');
        $sudahDiisi = DB::table('tblDATA_TUNJANGAN')->whereIn('nasabah_id', $pegawaiAktif)
            ->where('tahun', now()->year)->where('bulan', now()->month)->count();
        $belum = $pegawaiAktif->count() - $sudahDiisi;
        if ($belum > 0) {
            $bulan = now()->translatedFormat('F Y');
            $item[] = self::item('tunjangan-belum', 'Data Tunjangan', "Data tunjangan <strong>{$bulan}</strong> belum diisi untuk <strong>{$belum} pegawai</strong>.", route('bendahara.tunjangan.index'), 'blue', 'Bulan ini');
        }

        return $item;
    }

    private static function nasabahPegawai(User $user): ?array
    {
        $nasabah = $user->nasabah_id ? DB::table('tblNASABAH')->where('nasabah_id', $user->nasabah_id)->first() : null;
        if (! $nasabah || $nasabah->jenis_nasabah !== 'pegawai') {
            return null; // nasabah siswa: lonceng tidak diubah
        }

        $item = [];
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $pinjaman = DB::table('tblPINJAMAN')->where('nasabah_id', $nasabah->nasabah_id)
            ->orderByDesc('pinjaman_id')->first();

        if (! $pinjaman) {
            return $item;
        }

        $kode = '#PINJ-' . str_pad($pinjaman->pinjaman_id, 4, '0', STR_PAD_LEFT);

        if ($pinjaman->status_pinjaman === 'diajukan') {
            $item[] = self::item('status-diajukan', 'Status Pinjaman', "Pengajuan {$kode} (" . $rp($pinjaman->nominal_pinjaman) . ') sedang dinilai kelayakannya oleh Bendahara Bank Mini.', route('nasabah.pinjaman'), 'blue', 'Diproses');
        } elseif ($pinjaman->status_pinjaman === 'layak') {
            $item[] = self::item('status-layak', 'Status Pinjaman', "Pengajuan {$kode} <strong>disetujui</strong> dan menunggu pencairan dana.", route('nasabah.pinjaman'), 'emerald', 'Disetujui');
        } elseif ($pinjaman->status_pinjaman === 'ditolak') {
            $nilai = DB::table('tblKELAYAKAN')->where('pinjaman_id', $pinjaman->pinjaman_id)->first();
            if ($nilai && Carbon::parse($nilai->tgl_nilai)->gte(now()->subDays(30))) {
                $item[] = self::item('status-ditolak', 'Status Pinjaman', "Pengajuan {$kode} <strong>tidak disetujui</strong>: " . e($nilai->catatan), route('nasabah.pinjaman'), 'red', 'Ditolak');
            }
        } elseif ($pinjaman->status_pinjaman === 'cair') {
            $berikut = DB::table('tblJADWAL_ANGSURAN')->where('pinjaman_id', $pinjaman->pinjaman_id)
                ->where('status_bayar', 'belum')->orderBy('angsuran_ke')->first();
            if ($berikut) {
                $tempo = Carbon::parse($berikut->jatuh_tempo);
                $total = $rp($berikut->pokok + $berikut->bunga);
                if ($tempo->lt(today())) {
                    $item[] = self::item('angsuran-lewat', 'Jadwal Angsuran', "Angsuran ke-{$berikut->angsuran_ke} ({$total}) <strong>lewat jatuh tempo</strong> sejak " . $tempo->format('d/m/Y') . '. Segera hubungi Bank Mini.', route('nasabah.angsuran'), 'red', 'Lewat tempo');
                } elseif ($tempo->lte(today()->addDays(7))) {
                    $item[] = self::item('angsuran-dekat', 'Jadwal Angsuran', "Angsuran ke-{$berikut->angsuran_ke} ({$total}) jatuh tempo <strong>" . $tempo->format('d/m/Y') . '</strong>.', route('nasabah.angsuran'), 'amber', $tempo->isToday() ? 'Hari ini' : (int) abs(today()->diffInDays($tempo)) . ' hari lagi');
                }
            }
        }

        return $item;
    }

    private static function item(string $kode, string $judul, string $pesan, string $url, string $warna, string $label): array
    {
        return compact('kode', 'judul', 'pesan', 'url', 'warna', 'label');
    }
}
