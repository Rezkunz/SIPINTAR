# PANDUAN MOCKUP FIGMA & DOKUMEN LAPORAN BAB IV.2.6

Seluruh file di dalam folder ini adalah file **Mockup Mandiri (*Self-Contained*)**.
Artinya: **seluruh CSS, tata letak grid/flexbox, palet warna, tipografi, dan ikon vektor sudah tertanam langsung di dalam masing-masing file**. Anda tidak akan menemukan tampilan yang kosongan/berantakan lagi saat di-upload ke Figma.

---

## 🗂️ Susunan Mockup Berdasarkan Kelompok Pengguna (*Role*)

### 1. Kelompok Autentikasi (Publik / Seluruh Pengguna)
- [`01_auth_login.html`](./01_auth_login.html) : **Mockup Halaman Masuk (*Login*)**  
  *Layout split-panel:* Banner seni gelombang dinamis (*Green Wave Panel*) di kiri dan form input kapsul (*Pill Controls*) di kanan.
- [`02_auth_register.html`](./02_auth_register.html) : **Mockup Halaman Buka Rekening (*Register*)**  
  Formulir pendaftaran akun nasabah baru (kategori siswa/pegawai, NISN, nama lengkap, kontak WhatsApp).

---

### 2. Kelompok Portal Mandiri Nasabah
- [`03_nasabah_siswa_dashboard.html`](./03_nasabah_siswa_dashboard.html) : **Mockup Portal Nasabah Siswa**  
  Dilengkapi kartu buku tabungan digital (*Virtual Passbook Obsidian-Gold*), ringkasan saldo aktif, akumulasi setoran/penarikan, dan tabel 5 mutasi terakhir.
- [`04_nasabah_pegawai_dashboard.html`](./04_nasabah_pegawai_dashboard.html) : **Mockup Portal Nasabah Guru & Pegawai**  
  Dilengkapi buku tabungan digital pegawai, kartu pemantauan pinjaman aktif (*Loan Progress Bar*), info angsuran payroll gaji, dan mutasi.

---

### 3. Kelompok Front Office (Operasional Loket Kasir)
- [`05_teller_dashboard.html`](./05_teller_dashboard.html) : **Mockup Loket Kasir Teller**  
  Menampilkan 4 kartu indikator keuangan harian (kas masuk, kas keluar, antrean draft), tombol transaksi cepat loket, dan tabel antrean verifikasi setoran siswa.
- [`05_b_teller_buka_rekening.html`](./05_b_teller_buka_rekening.html) : **Mockup Layanan Membuka Rekening Baru (Otorisasi Teller)**  
  Menampilkan antrean calon nasabah berstatus *pending*, pemeriksaan kartu identitas fisik, dan modal aktivasi rekening dengan penerimaan setoran awal tunai (min. Rp 10.000). Sesuai dengan Use Case *"Membuka Rekening"* pada Use Case Diagram.

---

### 4. Kelompok Back Office (Pembiayaan & Kredit)
- [`06_bendahara_dashboard.html`](./06_bendahara_dashboard.html) : **Mockup Dashboard Bendahara Pinjaman**  
  Menampilkan metrik portofolio pinjaman aktif, permohonan baru, tabel review kelayakan kredit guru/staf, dan tombol otorisasi pencairan dana.

---

### 5. Kelompok Eksekutif (Pimpinan & Audit Keuangan)
- [`07_manager_dashboard.html`](./07_manager_dashboard.html) : **Mockup Ringkasan Eksekutif Manager**  
  Menampilkan posisi Dana Pihak Ketiga (DPK), posisi kas fisik brankas sekolah, saldo bank mitra penampung, rasio likuiditas kas, dan menu tindakan strategis pimpinan.

---

## 🚀 Cara Import ke Figma (Hanya Drag & Drop)

1. Buka aplikasi **Figma**.
2. Buka menu **Plugins** -> pilih **`html.to.design`**.
3. Di tab **"Upload HTML"**, Anda tinggal *drag-and-drop* salah satu file HTML di atas (misal `01_auth_login.html` atau `03_nasabah_siswa_dashboard.html`).
4. Figma akan langsung membentuk **Frame Desain High-Fidelity Asli** lengkap dengan teks yang bisa diedit, warna, tombol, dan kartu yang rapi.
