# Dokumentasi Modul: Pinjaman, Angsuran, dan Kas (SIPINTAR)
**Bank Mini Wiyata Cipta Niaga — SMK Negeri 1 Lemahabang**

Modul ini mengimplementasikan tata kelola pembiayaan pegawai (guru/staf), penilaian kelayakan kredit, pembentukan jadwal angsuran flat 2% per bulan, **pemotongan gaji/payroll berkala dengan alur verifikasi dua arah (*Two-Way Handshake*)**, pencatatan angsuran tunai & koreksi balik, kartu piutang digital, serta buku kas harian & kas bank.

---

## 1. Alur Bisnis Koordinasi Potongan Gaji (*Two-Way Handshake*)

```
[ Bendahara Bank Mini ] ──(1. Buat Rencana Potongan Bulanan)──► Status: "DIKIRIM"
           │                                                              │
           │                                                              ▼
           │                                            [ Bendahara Sekolah ]
           │                                            - Pantau daftar potongan
           │                                            - Unduh Format Bank (CSV UTF-8)
           │                                            - Teruskan ke Bank Mitra Payroll (BJB/BRI/dsb.)
           │                                                              │
           │                                                              ▼
           │                                            (Gaji dipotong oleh Bank Mitra luar)
           │                                                              │
           │                                                              ▼
           │                                            [ Input Hasil Pemotongan ]
           │                                            - Tandai Berhasil / Gagal per pegawai
           │                                            - Pilih metode: Tunai / Transfer Bank
           │                                            - Status paket: "MENUNGGU_VERIFIKASI"
           │                                            - Uang diserahkan ke Bank Mini
           │                                                              │
           ▼                                                              │
[ Konfirmasi Penerimaan Kas ] ◄───────────────────────────────────────────┘
- Pilih Kas Masuk (Kas Harian Tunai / Kas Rekening Bank Giro)
- Verifikasi nominal fisik/rekening
- Sistem otomatis:
  1. Catat kas masuk di tblMUTASI_KAS & tambah saldo tblKAS
  2. Catat baris angsuran tblANGSURAN untuk tiap pegawai sukses
  3. Kurangi sisa piutang (auto lunas jika sisa 0)
  4. Status paket: "SELESAI"
  5. Terbitkan Berita Acara Serah Terima (BAST) resmi
```

---

## 2. Struktur Database & Skema yang Digunakan

### Tabel `tblRENCANA_POTONGAN`
- `rencana_potongan_id` (Primary Key)
- `periode_id` (Foreign Key ke `tblPERIODE`)
- `user_id` (ID Bendahara Bank Mini pembuat paket)
- `total_potongan` (Total nominal yang direncanakan dipotong)
- `tgl_potongan` (Tanggal eksekusi pemotongan gaji oleh bank mitra)
- `total_realisasi` (Total dana riil yang berhasil dipotong)
- `metode_penyerahan` (`tunai` atau `transfer`)
- `catatan_sekolah` (Catatan serah terima dari Bendahara Sekolah)
- `status_potongan` (`draft`, `dikirim`, `menunggu_verifikasi`, `selesai`)

### Tabel `tblDETAIL_POTONGAN`
- `detail_potongan_id` (Primary Key)
- `rencana_potongan_id` (Foreign Key ke `tblRENCANA_POTONGAN`)
- `jadwal_angsuran_id` (Foreign Key ke `tblJADWAL_ANGSURAN`)
- `nominal_rencana` (Nominal pokok + bunga yang direncanakan)
- `hasil_potongan` (`berhasil`, `gagal`)
- `nominal_realisasi` (Nominal aktual yang berhasil dipotong)
- `keterangan` (Catatan jika gagal, misal: cuti / gaji tidak cukup)

### Tabel `tblANGSURAN` & `tblMUTASI_KAS`
- `tblANGSURAN` hanya dibukukan saat Bendahara Bank Mini menerima uang secara sah.
- `tblMUTASI_KAS` mencatat uang masuk ke kas tunai atau kas bank penampung.

---

## 3. Daftar Endpoint & Halaman per Peran

### A. Bendahara Sekolah (`role:bendahara`)
| URL | Route Name | Keterangan |
|---|---|---|
| `/bendahara/potongan` | `bendahara.potongan.index` | Monitoring seluruh paket rencana potongan |
| `/bendahara/potongan/download/{id}` | `bendahara.potongan.download` | **Unduh format Bank Mitra Payroll (CSV UTF-8)** |
| `/bendahara/potongan/hasil/{id}` | `bendahara.potongan.hasil` | Form input hasil realisasi potongan gaji per pegawai |
| `/bendahara/potongan/bast/{id}` | `bendahara.potongan.bast` | **Cetak Berita Acara Serah Terima (BAST)** |

### B. Bendahara Bank Mini (`role:bendahara_mini`)
| URL | Route Name | Keterangan |
|---|---|---|
| `/bbm/angsuran/potongan` | `bbm.angsuran.potongan` | Penyusunan paket rencana potongan bulanan |
| `/bbm/angsuran/potongan/{id}` | `bbm.angsuran.potongan.detail` | **Review hasil sekolah & form terima kas** |
| `/bbm/angsuran/potongan/{id}/konfirmasi` | `bbm.angsuran.potongan.konfirmasi` | **Eksekusi penerimaan kas & bukukan angsuran** |
| `/bbm/angsuran/potongan/{id}/bast` | `bbm.angsuran.potongan.bast` | Cetak Berita Acara Serah Terima (BAST) |
| `/bbm/pinjaman` | `bbm.pinjaman.index` | Monitoring seluruh pinjaman |
| `/bbm/pinjaman/ajukan` | `bbm.pinjaman.ajukan` | Form pengajuan pinjaman pegawai |
| `/bbm/pinjaman/kelayakan` | `bbm.pinjaman.kelayakan` | Analisis kelayakan peminjam |
| `/bbm/pinjaman/cair` | `bbm.pinjaman.cair` | Pencairan pinjaman & kas keluar |
| `/bbm/angsuran/langsung` | `bbm.angsuran.langsung` | Pembayaran angsuran tunai loket Bank Mini |
| `/bbm/angsuran/koreksi` | `bbm.angsuran.koreksi` | Koreksi pembalik angsuran *non-destructive* |
| `/bbm/kartu` | `bbm.kartu.index` | Kartu piutang digital individu |
| `/bbm/kartu/rekap` | `bbm.kartu.rekap` | Rekapitulasi portofolio piutang |
| `/kas/harian` | `kas.harian` | Buku kas harian tunai |
| `/kas/bank` | `kas.bank` | Buku kas rekening giro / bank |

### C. Nasabah Pegawai (`role:nasabah`)
| URL | Route Name | Keterangan |
|---|---|---|
| `/nasabah/angsuran` | `nasabah.angsuran` | Jadwal angsuran & sisa piutang pribadi (Anti-IDOR) |

---

## 4. Cara Menjalankan & Pengujian

### 1. Jalankan Seeder Demo UAT:
```bash
php artisan db:seed --class=PinjamanKasDemoSeeder
```
*Seeder ini langsung menyiapkan paket potongan bulan berjalan yang siap diunduh dan dicatat oleh Bendahara Sekolah, serta diuji serah terimanya ke Bendahara Bank Mini.*

### 2. Jalankan Pengujian Otomatis:
```bash
php artisan test --filter=Pinjaman
```
Hasil:
- `Tests: 10 passed (64 assertions)`
- Termasuk pengujian siklus penuh: `test_alur_lengkap_potongan_sekolah_hingga_konfirmasi_kas_bank_mini`.
