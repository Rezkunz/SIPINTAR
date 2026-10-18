# Product Requirement Document (PRD)
# SIPINTAR — Sistem Perbankan Bank Mini Sekolah

**Versi Dokumen:** 1.0  
**Tanggal:** 11 Oktober 2026  
**Status:** Baseline Active  
**Target Pengguna:** Siswa, Guru/Karyawan (Nasabah), Siswa Praktik, Teller, Bendahara Sekolah, Bendahara Bank Mini, Manager Bank Mini  

---

## 1. Ringkasan Eksekutif & Latar Belakang Produk

### 1.1 Visi Produk
**SIPINTAR** (*Sistem Informasi Perbankan Bank Mini Sekolah Pintar*) adalah aplikasi perbankan berbasis web yang dirancang khusus untuk memfasilitasi tata kelola operasional **Bank Mini Sekolah** dan laboratorium perbankan kejuruan. 

Aplikasi ini menyeimbangkan dua tujuan utama:
1. **Pendidikan Vokasi:** Menjadi sarana simulasi langsung (*hands-on practice*) bagi siswa jurusan Akuntansi/Perbankan Syariah/Keuangan dalam mengoperasikan alur kerja teller dan perbankan riil.
2. **Akuntabilitas Finansial Sekolah:** Mengelola simpanan riil nasabah (siswa, guru, tenaga kependidikan), pembiayaan/pinjaman mikro sekolah, kas harian (*vault / cash drawer*), serta rekapitulasi mutasi yang transparan dan dapat diaudit.

### 1.2 Masalah yang Diselesaikan
- Pencatatan tabungan manual menggunakan buku fisik rawan selisih, rusak, atau hilang.
- Siswa praktik membutuhkan platform dengan terminologi dan tata kelola perbankan standar industri tanpa kompleksitas sistem core banking yang membingungkan.
- Aksesibilitas nasabah untuk memantau saldo mandiri dan jadwal angsuran secara real-time.

---

## 2. Peran Pengguna & Hak Akses (User Personas & RBAC)

Sistem menerapkan **Role-Based Access Control (RBAC)** berbasis middleware multi-role dengan 6 peran spesifik:

| Peran | Identifier Login | Tanggung Jawab Utama | Akses Menu |
| :--- | :--- | :--- | :--- |
| **Nasabah** | NISN / NIP (contoh: `0059876543`) | Pemilik rekening simpanan/pinjaman (siswa/guru). Melihat mutasi rekening secara mandiri. | Ringkasan Saldo, Mutasi Rekening, Jadwal Angsuran Pinjaman |
| **Siswa Praktik** | NISN (contoh: `0061234567`) | Siswa magang/praktik di lab bank mini. Melakukan input transaksi dalam batas limit terawasi. | Papan Transaksi, Buka Rekening Baru, Simpanan, Penarikan, Log Aktivitas |
| **Teller** | Kode Pegawai (contoh: `TLR-001`) | Petugas operasional harian kasir/teller. Melayani setoran, penarikan, verifikasi identitas nasabah, dan rekonsiliasi kas harian. | Menu Siswa Praktik + Buka Kas/Tutup Kas Harian, Validasi Otorisasi, Bukti Cetak Kuitansi |
| **Bendahara** | Kode Pegawai (contoh: `BND-001`) | Pengelola keuangan sekolah induk. Meninjau neraca kas sekolah dan transfer alokasi. | Laporan Keuangan Sekolah, Rekap Arus Kas Masuk/Keluar, Rekonsiliasi Bank Induk |
| **Bendahara Bank Mini** | Kode Pegawai (contoh: `BBM-001`) | Penanggung jawab likuiditas fisik kas bank mini (*vault custodian*), pengeluaran operasional, dan modal kerja. | Manajemen Kas Brankas (*Vault*), Persetujuan Penarikan Besar, Monitoring Kas Teller |
| **Manager** | Kode Pegawai (contoh: `MGR-001`) | Pimpinan/Kepala Lab Bank Mini. Meninjau performa, persetujuan kredit pinjaman, audit berkala, dan manajemen pengguna. | Dashboard Eksekutif, Persetujuan Pinjaman, Laporan Tabungan & Portofolio, Manajemen User |

---

## 3. Spesifikasi Fungsional

### 3.1 Autentikasi & Keamanan Sesi
- **Identifikasi Unik:** Login menggunakan **Nomor Unik Sahaja** (NISN untuk siswa/nasabah siswa, Kode Unik Pegawai seperti `TLR-001`, `BND-001`, `MGR-001`). Tidak mewajibkan email pada antarmuka login.
- **Kebijakan Sesi Perangkat Bersama (*Shared Devices*):** Tidak mengaktifkan opsi *Remember Me* berkepanjangan pada browser kasir/lab demi mencegah penyalahgunaan rekening setelah pengguna meninggalkan perangkat.
- **Interaksi Kata Sandi:** Tombol toggle password menggunakan ikon SVG mata (*eye / eye-off*) vektor yang teruji ergonomis.
- **Perlindungan Rute:** Menggunakan `RoleMiddleware` untuk menjamin pengguna hanya dapat mengakses endpoint yang sesuai hak perannya.

### 3.2 Modul Rekening & Transaksi Tabungan
- **Pembukaan Rekening:** Penerbitan nomor rekening otomatis berbasis format standar: `[Kode-Kategori][Tahun][Urutan]` (misal `1001-2026-00042`).
- **Setoran Tunai (*Deposit*):** Pencatatan nominal, pencetakan slip setoran, dan penambahan saldo efektif seketika (*instant balance update*).
- **Penarikan Tunai (*Withdrawal*):** Pengecekan saldo mengendap minimum, batas limit per transaksi, dan verifikasi tandatangan/identitas nasabah.
- **Buku Mutasi:** Riwayat debit, kredit, saldo akhir, serta kode unik referensi transaksi.

### 3.3 Modul Fasilitas Pinjaman / Pembiayaan Mikro
- **Pengajuan Pinjaman:** Simulasi plafon, tenor (bulan), bagi hasil / margin administrasi, dan nominal angsuran per bulan.
- **Workflow Persetujuan (*Approval Workflow*):**
  1. *Pengajuan* oleh nasabah / admin.
  2. *Verifikasi Berkas* oleh Teller / Bendahara Bank Mini.
  3. *Persetujuan / Penolakan (*Approval/Rejection*)* final oleh **Manager**.
- **Jadwal & Kartu Angsuran:** Pelacakan status angsuran (Lunas, Belum Bayar, Jatuh Tempo, Denda).

### 3.4 Modul Kas Harian & Rekonsiliasi (*Cash Drawer & Vault*)
- **Buka Kas (*Opening Cash*):** Teller menginput modal awal kas laci di pagi hari yang diserahkan oleh Bendahara Bank Mini.
- **Tutup Kas (*Closing Cash & End of Day Balancing*):** Perhitungan fisik kas akhir (*cash count*) dibandingkan dengan total transaksi sistem untuk memastikan nihil selisih (*zero variance*).

### 3.5 Modul Laporan & Ekspor Data
- Rekapitulasi tabungan harian, mingguan, dan bulanan.
- Laporan kolektibilitas angsuran pinjaman.
- Rekapitulasi arus kas masuk dan keluar (*cash flow statement*).
- Fitur ekspor ke format PDF (slip kuitansi & rekening koran) dan Excel/CSV (audit data).

---

## 4. Desain Sistem & Standar Antarmuka (Taste & Anti-Slop)

Antarmuka SIPINTAR dibangun berdasarkan panduan keanggunan institusional perbankan (*Taste Skill*) dan kebersihan kode tanpa dekorasi artifisial (*Anti-Slop*):

### 4.1 Identitas Visual & Palet Warna
- **Brand Identity:** Simbol circular seal klasik bermotif trio bulatan emas bersatu (*trefoil/interlocking triad*) yang melambangkan transparansi, keadilan, dan pertumbuhan bersama.
- **Palet Warna Utama:**
  - *Deep Forest Teal* (`#042f2e` & `#0f766e`): Memberikan kesan kokoh, terpercaya, dan stabilitas institusi keuangan.
  - *Warm Amber Gold* (`#d97706` & `#f59e0b`): Aksen pertumbuhan aset, nilai finansial, dan kehangatan sekolah.
  - *Neutral Canvas* (`#f8fafc` & `#ffffff`): Latar belakang bersih untuk kenyamanan membaca angka transaksi berjam-jam.
- **Tipografi:** Plus Jakarta Sans dengan rasio hierarki ukuran huruf yang konsisten (Heading, Subheading, Body, Monospace untuk angka kode transaksi).
- **Ikonografi:** Ikon SVG vektor murni (tanpa ketergantungan font icon berat dan tanpa emoji di antarmuka produksi).

### 4.2 Struktur CSS Modular
Seluruh styling dipisahkan secara terstruktur ke dalam file CSS terisolasi:
- `public/css/app.css`: Token global warna, layout grid dashboard, navigasi samping (*sidebar*), kartu KPI metrik, dan tabel transaksi.
- `public/css/auth.css`: Desain responsif split-screen portal login.

---

## 5. Arsitektur Teknis & Struktur Data

### 5.1 Tech Stack
- **Framework:** Laravel 11.x (PHP 8.2+)
- **Database:** MySQL / SQLite
- **Frontend Layer:** Blade Templating Engine + Vanilla Modular CSS + Vanilla JavaScript (tanpa overhead JS framework untuk performa cepat di lab komputer)
- **Web Server:** PHP Built-in Server / Nginx

### 5.2 Skema Relasi Database Utama
- `users`: ID, nama, no_induk, no_identitas, email, password, role (`nasabah`, `siswa`, `teller`, `bendahara`, `bendahara_mini`, `manager`), status_aktif.
- `rekening`: ID, user_id, no_rekening, jenis_simpanan, saldo_efektif, status.
- `transaksi`: ID, rekening_id, teller_id, no_referensi, jenis (`setor`, `tarik`, `transfer`), jumlah, saldo_sebelum, saldo_sesudah, keterangan, created_at.
- `pinjaman`: ID, rekening_id, manager_id, no_akad, plafon, tenor_bulan, angsuran_per_bulan, status (`diajukan`, `disetujui`, `lunas`, `ditolak`).
- `angsuran`: ID, pinjaman_id, angsuran_ke, tanggal_jatuh_tempo, nominal, tanggal_bayar, status.
- `kas_teller`: ID, teller_id, tanggal, saldo_awal, saldo_akhir_sistem, saldo_akhir_fisik, selisih, status (`open`, `balanced`, `closed`).

### 5.3 Struktur Direktori Modular MVC Berbasis Aktor (Role-Driven)
Setiap aktor/peran memiliki ruang kerja controller dan tampilan (*views*) terisolasi:

```text
app/Http/Controllers/
├── Auth/              # Autentikasi Login/Logout
├── Nasabah/           # Controller Khusus Nasabah
├── SiswaPraktik/      # Controller Khusus Siswa Praktik
├── Teller/            # Controller Khusus Kasir / Loket Teller
├── Bendahara/         # Controller Khusus Pinjaman & Angsuran Sekolah
├── BendaharaMini/     # Controller Khusus Kas Harian & Kas Bank
├── Manager/           # Controller Khusus Eksekutif & Laporan
└── DashboardController.php # Router delegator utama /dashboard

resources/views/
├── layouts/           # Template bersama (app.blade.php)
├── auth/              # Form login
├── nasabah/           # View portal nasabah
├── siswa_praktik/     # View simulasi praktik siswa
├── teller/            # View operasional kasir teller
├── bendahara/         # View modul pinjaman bendahara sekolah
├── bendahara_mini/    # View modul buku kas bendahara bank mini
└── manager/           # View eksekutif & audit manager
```

---

## 6. Roadmap Pengembangan Selanjutnya (Next Phase)

```
[Fase 1: Baseline UI & Autentikasi Multi-Role]  -->  (SELESAI)
   - Layout modern Taste Skill & Anti-Slop
   - Login nomor unik (NISN / ID Pegawai)
   - Pengamanan role middleware 6 aktor

[Fase 2: Transaksi Teller & Validasi Cetak]      -->  (PRIORITAS BERIKUTNYA)
   - Antarmuka teller untuk setor/tarik instan
   - Generator nomor slip transaksi dan cetak termal/PDF
   - Validasi saldo minimum mengendap

[Fase 3: Modul Persetujuan Pinjaman Manager]
   - Form pengajuan pinjaman online
   - Tabel persetujuan berkas untuk Manager
   - Generator jadwal angsuran otomatis

[Fase 4: Rekonsiliasi Tutup Kas Harian & Audit]
   - Form opname kas harian teller dan vault bendahara
   - Laporan audit log aktivitas siswa praktik

[Fase 5: Notifikasi & Kartu Digital Nasabah]
   - Pembuatan kartu rekening digital berbasis QR Code / Barcode NISN
   - Notifikasi mutasi ke nomor orang tua / wali siswa
```

---

## 7. Kriteria Keberhasilan (Acceptance Criteria)

1. **Kecepatan Operasional:** Teller dapat menyelesaikan pencatatan transaksi setoran tunai dalam waktu kurang dari 30 detik.
2. **Kesesuaian Desain:** Antarmuka bersih, elegan, bebas dari emoji kasual yang tidak sesuai standar perbankan, dan konsisten di seluruh layar resolusi lab komputer.
3. **Integritas Transaksi:** Tidak ada mutasi yang tersimpan tanpa nomor referensi unik dan jejak pencatat (*audit trail user ID*).
