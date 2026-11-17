/**
 * SIPINTAR — Basis pengetahuan FAQ chatbot + mesin pencocokan pertanyaan.
 *
 * Setiap entri:
 *   id     : pengenal unik
 *   peran  : siapa yang boleh mendapat jawaban ini
 *            ('semua', 'nasabah' = pegawai & siswa, 'petugas' = semua staf, atau peran spesifik)
 *   tanya  : contoh pertanyaan (dipakai untuk chip saran)
 *   kunci  : kata/frasa kunci (huruf kecil) yang menunjukkan MAKSUD pertanyaan. Frasa >1 kata dicocokkan utuh.
 *   umum   : (opsional) kata benda umum berbobot rendah, mis. "pinjaman", agar kata bermaksud
 *            seperti "ditolak", "syarat", "lunasi" tetap menentukan jawaban.
 *   jawab  : jawaban (HTML sederhana)
 *   menu   : (opsional) tombol pintasan [label, path]
 *
 * Aturan bisnis di jawaban mengikuti kode aplikasi (validasi controller & config/sipintar.php).
 */
(function () {
    'use strict';

    const PETUGAS = ['manager', 'teller', 'siswa_praktik', 'bendahara', 'bendahara_mini'];
    const NASABAH = ['nasabah_pegawai', 'nasabah_siswa'];

    const FAQ = [
        // ───────────────────────── UMUM ─────────────────────────
        { id: 'salam', peran: ['semua'], tanya: 'Halo',
          kunci: ['halo', 'hai', 'hello', 'hi', 'hallo', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam', 'assalamualaikum', 'permisi', 'pagi', 'siang'],
          jawab: 'Halo! 😊 Saya asisten SIPINTAR. Tanyakan apa saja seputar menu dan transaksi Anda, atau ketik <strong>topik</strong> untuk melihat daftar pertanyaan.' },

        { id: 'terima-kasih', peran: ['semua'], tanya: 'Terima kasih',
          kunci: ['terima kasih', 'makasih', 'thanks', 'thank you', 'trims', 'mantap', 'oke siap', 'sip'],
          jawab: 'Sama-sama! 😊 Kalau ada yang lain, tanyakan saja.' },

        { id: 'topik', peran: ['semua'], tanya: 'Apa saja yang bisa ditanyakan?',
          kunci: ['topik', 'bantuan', 'help', 'bisa apa', 'daftar pertanyaan', 'menu bantuan', 'faq', 'tanya apa', 'bisa ditanyakan'],
          umum: ['apa saja'],
          jawab: '__TOPIK__' },

        { id: 'tentang', peran: ['semua'], tanya: 'Apa itu SIPINTAR?',
          kunci: ['apa itu sipintar', 'sipintar itu', 'tentang aplikasi', 'aplikasi apa', 'bank mini itu', 'apa itu bank mini'],
          jawab: '<strong>SIPINTAR</strong> adalah sistem Bank Mini Sekolah untuk <strong>tabungan</strong> siswa & pegawai, <strong>pinjaman</strong> guru & karyawan (angsuran lewat potong tunjangan atau bayar langsung), pencatatan <strong>kas</strong>, serta <strong>laporan keuangan</strong> untuk Manajer.' },

        { id: 'jam', peran: ['semua'], tanya: 'Jam operasional Bank Mini?',
          kunci: ['jam operasional', 'jam buka', 'jam kerja', 'jam layanan', 'kapan buka', 'buka jam', 'hari libur', 'buka hari', 'loket buka'],
          jawab: '🕐 <strong>Jam Operasional Bank Mini:</strong><br>Senin – Jumat: 07.30 – 14.00 WIB<br>Sabtu: 07.30 – 11.00 WIB<br>Minggu & hari libur: <em>tutup</em>' },

        { id: 'kontak', peran: ['semua'], tanya: 'Bagaimana menghubungi admin?',
          kunci: ['hubungi', 'kontak', 'admin', 'whatsapp', 'nomor wa', 'email', 'telepon', 'customer service', 'cs', 'pengaduan', 'komplain'],
          jawab: '📞 <strong>Hubungi Bank Mini Sekolah:</strong><br>• Loket Teller – Gedung Keuangan<br>• WhatsApp: <strong>0812-3456-7890</strong><br>• Email: <strong>bankmini@sipintar.sch.id</strong>' },

        { id: 'login', peran: ['semua'], tanya: 'Cara login ke SIPINTAR?',
          kunci: ['cara login', 'cara masuk', 'gagal login', 'tidak bisa login', 'gak bisa login', 'ga bisa masuk', 'tidak bisa masuk', 'login pakai apa', 'login'],
          jawab: '🔑 <strong>Login</strong> memakai salah satu: <strong>nomor HP</strong>, <strong>NISN/NIP/kode pegawai</strong>, atau <strong>email</strong>, ditambah kata sandi.<br>• Salah 5 kali berturut-turut → tunggu <strong>60 detik</strong> sebelum mencoba lagi.<br>• Akun <strong>nonaktif</strong> tidak bisa login; hubungi Manajer Bank Mini.' },

        { id: 'lupa-sandi', peran: ['semua'], tanya: 'Saya lupa kata sandi',
          kunci: ['lupa sandi', 'lupa password', 'lupa kata sandi', 'reset password', 'reset sandi', 'sandi lupa', 'password lupa', 'lupa'],
          jawab: '🔒 Kata sandi yang terlupa direset oleh <strong>Manajer Bank Mini</strong> lewat menu Kelola Pengguna. Datang ke loket atau hubungi admin, lalu ganti kata sandi baru Anda lewat <strong>Edit Profil Saya</strong> setelah berhasil masuk.' },

        { id: 'ganti-sandi', peran: ['semua'], tanya: 'Cara ganti kata sandi / profil?',
          kunci: ['ganti sandi', 'ubah sandi', 'ganti password', 'ubah password', 'ganti kata sandi', 'edit profil', 'ubah profil', 'ganti nomor hp', 'ubah nomor hp', 'ganti email', 'profil saya'],
          jawab: '👤 Klik <strong>nama Anda di pojok kanan atas</strong> → <strong>Edit Profil Saya</strong>. Di sana Anda dapat mengubah nomor WhatsApp/email dan mengganti kata sandi (wajib mengisi kata sandi lama).' },

        { id: 'logout', peran: ['semua'], tanya: 'Cara keluar (logout)?',
          kunci: ['logout', 'log out', 'sign out', 'cara keluar', 'keluar akun', 'keluar aplikasi'],
          jawab: '🚪 Klik <strong>nama Anda di pojok kanan atas</strong> → <strong>Sign Out / Keluar</strong>. Setelah keluar, halaman sebelumnya tidak bisa dibuka lagi tanpa login.' },

        { id: 'nonaktif', peran: ['semua'], tanya: 'Akun saya nonaktif / terkunci',
          kunci: ['nonaktif', 'dinonaktifkan', 'akun diblokir', 'akun terkunci', 'akun saya terkunci', 'akun mati', 'tidak aktif', 'terkunci', 'diblokir'],
          jawab: '⛔ Akun berstatus <strong>nonaktif</strong> tidak dapat login dan sesi yang sedang berjalan otomatis diputus. Hanya <strong>Manajer Bank Mini</strong> yang dapat mengaktifkan kembali lewat Kelola Pengguna.' },

        { id: 'periode-terkunci', peran: ['petugas'], tanya: 'Kenapa transaksi ditolak karena periode terkunci?',
          kunci: ['periode terkunci', 'periode ditutup', 'periode tertutup', 'ditutup oleh manajer', 'terkunci periode', 'transaksi ditolak', 'tidak bisa input tanggal'],
          jawab: '🔐 Bulan yang sudah <strong>ditutup buku</strong> oleh Manajer terkunci: semua transaksi bertanggal di bulan itu (setoran, angsuran, kas, pencairan) ditolak. Catat transaksi dengan tanggal di periode yang masih terbuka, atau minta Manajer <strong>membuka kembali</strong> periode tersebut bila ada koreksi.' },

        // ───────────────────────── NASABAH (SISWA & PEGAWAI) ─────────────────────────
        { id: 'saldo', peran: ['nasabah'], tanya: 'Berapa saldo tabungan saya?',
          kunci: ['saldo', 'cek saldo', 'berapa saldo', 'sisa saldo', 'uang saya', 'tabungan saya', 'jumlah tabungan'],
          jawab: '💰 Saldo terkini tampil di menu <strong>Riwayat Mutasi</strong> (kartu Saldo Efektif), lengkap dengan total setoran dan penarikan Anda.',
          menu: [['Buka Riwayat Mutasi', '/nasabah/saldo']] },

        { id: 'mutasi', peran: ['nasabah'], tanya: 'Lihat riwayat transaksi tabungan',
          kunci: ['mutasi', 'riwayat transaksi', 'riwayat tabungan', 'histori', 'history', 'transaksi saya', 'buku tabungan', 'lihat transaksi', 'transaksi bulan'],
          umum: ['transaksi'],
          jawab: '📒 Menu <strong>Riwayat Mutasi</strong> menampilkan seluruh setoran, penarikan, dan jasa tabungan beserta saldo akhir tiap transaksi. Gunakan filter <strong>Semua / Setoran / Penarikan</strong>.',
          menu: [['Buka Riwayat Mutasi', '/nasabah/saldo']] },

        { id: 'norek', peran: ['nasabah'], tanya: 'Berapa nomor rekening saya?',
          kunci: ['nomor rekening', 'no rekening', 'norek', 'no rek', 'rekening saya', 'nomer rekening'],
          jawab: '🔢 Nomor rekening tampil di halaman <strong>Informasi Rekening</strong> (dashboard) dan Riwayat Mutasi, dengan format <strong>1001-TAHUN-NOMOR</strong> (contoh: 1001-2026-00001).',
          menu: [['Informasi Rekening', '/dashboard']] },

        { id: 'setor-nasabah', peran: ['nasabah'], tanya: 'Cara setor tabungan?',
          kunci: ['setor', 'setoran', 'menabung', 'nabung', 'simpan uang', 'deposit', 'tambah saldo', 'isi saldo'],
          jawab: '🏦 <strong>Cara Setor Tunai:</strong><br>1. Datang ke loket Bank Mini pada jam operasional<br>2. Sebutkan nomor rekening Anda<br>3. Serahkan uang (minimal <strong>Rp1.000</strong>)<br>4. Terima bukti setoran; saldo langsung bertambah dan tampil di Riwayat Mutasi' },

        { id: 'tarik-nasabah', peran: ['nasabah'], tanya: 'Cara tarik tabungan?',
          kunci: ['tarik', 'penarikan', 'ambil uang', 'ambil tabungan', 'cairkan tabungan', 'tarik tunai', 'menarik', 'saldo minimal', 'harus tersisa', 'tersisa', 'sisa minimal'],
          jawab: '💸 <strong>Cara Tarik Tunai:</strong><br>1. Datang ke loket Bank Mini dengan identitas Anda<br>2. Sebutkan nomor rekening & jumlah penarikan<br>3. Teller memverifikasi lalu menyerahkan uang beserta bukti<br>⚠️ Saldo yang tersisa setelah penarikan minimal <strong>Rp10.000</strong>.' },

        { id: 'jasa-tabungan', peran: ['nasabah'], tanya: 'Berapa jasa (bunga) tabungan?',
          kunci: ['jasa tabungan', 'bunga tabungan', 'bagi hasil', 'bunga', 'jasa simpanan', 'keuntungan menabung', 'dapat bunga', 'dapat jasa', 'untung menabung'],
          jawab: '📈 <strong>Jasa tabungan</strong> dihitung dari saldo dan dibukukan otomatis setiap <strong>akhir bulan</strong>:<br>• Siswa: <strong>0,1% per bulan</strong><br>• Guru & karyawan: <strong>0,2% per bulan</strong><br>Jasa tampil di Riwayat Mutasi sebagai transaksi jasa tabungan.' },

        { id: 'aktivasi', peran: ['semua'], tanya: 'Bagaimana cara membuka / mengaktifkan rekening?',
          kunci: ['buka rekening', 'daftar rekening', 'rekening baru', 'registrasi', 'daftar akun', 'aktivasi', 'rekening belum aktif', 'menunggu aktivasi', 'pending', 'masih pending', 'rekening pending', 'belum aktif'],
          jawab: '🆕 <strong>Membuka rekening:</strong><br>1. Daftar lewat tombol <strong>Buka Rekening</strong> di halaman login (atau dibantu petugas)<br>2. Status rekening <strong>Menunggu Aktivasi</strong> — layanan transaksi masih terkunci<br>3. Datang ke loket dengan <strong>setoran awal minimal Rp10.000</strong><br>4. Teller/Siswa Praktik mengaktifkan rekening, lalu semua menu terbuka' },

        { id: 'salah-catat', peran: ['nasabah'], tanya: 'Saldo/transaksi saya tidak sesuai',
          kunci: ['saldo tidak sesuai', 'salah catat', 'transaksi salah', 'saldo salah', 'saldo berkurang', 'tidak sesuai', 'selisih'],
          jawab: '🧾 Bawa bukti transaksi Anda ke loket <strong>Teller</strong>. Teller dapat membuat <strong>koreksi transaksi</strong> (transaksi pembalik) sehingga riwayat lama tetap tercatat dan saldo menjadi benar.' },

        { id: 'privasi', peran: ['nasabah'], tanya: 'Apakah orang lain bisa melihat data saya?',
          kunci: ['privasi', 'data saya aman', 'orang lain bisa lihat', 'rahasia', 'keamanan data'],
          jawab: '🛡️ Data yang tampil selalu diambil dari akun yang sedang login. Nasabah lain tidak dapat membuka rekening, mutasi, atau pinjaman Anda — mengubah alamat halaman pun tidak akan menampilkan data orang lain.' },

        // ───────────────────────── NASABAH PEGAWAI ─────────────────────────
        { id: 'ajukan-pinjaman', peran: ['nasabah_pegawai'], tanya: 'Cara mengajukan pinjaman?',
          kunci: ['ajukan pinjaman', 'cara pinjam', 'mengajukan pinjaman', 'pinjam uang', 'mau pinjam', 'ingin pinjam', 'tenor', 'plafon', 'maksimal pinjaman', 'minimal pinjaman', 'paling lama'],
          umum: ['pinjaman', 'pinjam', 'kredit', 'utang', 'hutang', 'meminjam'],
          jawab: '📋 <strong>Mengajukan pinjaman:</strong><br>1. Buka menu <strong>Ajukan Pinjaman</strong><br>2. Isi plafon <strong>Rp500.000 – Rp30.000.000</strong> dan tenor <strong>1–36 bulan</strong> (simulasi cicilan tampil otomatis)<br>3. Pilih metode bayar: <strong>potong tunjangan</strong> atau <strong>setor langsung</strong> di loket<br>4. Kirim → status <strong>Menunggu Penilaian Kelayakan</strong> oleh Bendahara Bank Mini',
          menu: [['Ajukan Pinjaman', '/nasabah/pinjaman/ajukan']] },

        { id: 'syarat-pinjaman', peran: ['nasabah_pegawai'], tanya: 'Apa syarat mengajukan pinjaman?',
          kunci: ['syarat', 'persyaratan', 'ketentuan pinjaman', 'siapa yang boleh pinjam', 'boleh pinjam', 'kriteria'],
          jawab: '✅ <strong>Syarat pinjaman:</strong><br>• Guru atau tenaga kependidikan (pegawai)<br>• Rekening tabungan sudah <strong>aktif</strong><br>• Tidak sedang memiliki pinjaman yang berjalan (diajukan / disetujui / cair)<br>• Cicilan per bulan wajar terhadap penghasilan (batas rasio <strong>40%</strong> dari gaji + tunjangan)' },

        { id: 'jasa-pinjaman', peran: ['nasabah_pegawai', 'bendahara_mini'], tanya: 'Berapa jasa pinjaman & cicilan per bulan?',
          kunci: ['jasa pinjaman', 'bunga pinjaman', 'berapa cicilan', 'hitung angsuran', 'hitung cicilan', 'simulasi', 'biaya pinjaman', 'besar angsuran', 'cicilan per bulan'],
          jawab: '🧮 Jasa pinjaman <strong>flat 2% per bulan</strong> dari plafon awal.<br>Cicilan/bulan = plafon ÷ tenor + 2% × plafon.<br>Contoh: Rp5.000.000, 10 bulan → pokok Rp500.000 + jasa Rp100.000 = <strong>Rp600.000/bulan</strong>.' },

        { id: 'status-pinjaman', peran: ['nasabah_pegawai'], tanya: 'Bagaimana status pengajuan pinjaman saya?',
          kunci: ['status pengajuan', 'status pinjaman', 'sudah disetujui', 'disetujui belum', 'kapan cair', 'info pinjaman', 'sudah diproses'],
          umum: ['pengajuan saya', 'pinjaman saya'],
          jawab: '🔎 Cek menu <strong>Info Pinjaman</strong>. Urutan status:<br><strong>Menunggu Penilaian</strong> → <strong>Disetujui (menunggu pencairan)</strong> → <strong>Cair</strong> → <strong>Lunas</strong>.<br>Bila tidak layak, status menjadi <strong>Ditolak</strong> beserta catatan penilaian.',
          menu: [['Info Pinjaman', '/nasabah/pinjaman']] },

        { id: 'jadwal-angsuran', peran: ['nasabah_pegawai'], tanya: 'Kapan jatuh tempo angsuran saya?',
          kunci: ['jadwal angsuran', 'jatuh tempo', 'angsuran saya', 'cicilan saya', 'sisa pinjaman', 'sisa utang', 'sisa hutang', 'sisa piutang'],
          umum: ['angsuran', 'cicilan'],
          jawab: '📅 Menu <strong>Jadwal Angsuran</strong> menampilkan setiap angsuran (tanggal jatuh tempo, pokok, jasa, status Lunas/Belum) serta <strong>sisa pokok pinjaman</strong> Anda.',
          menu: [['Jadwal Angsuran', '/nasabah/angsuran']] },

        { id: 'bayar-angsuran-nasabah', peran: ['nasabah_pegawai'], tanya: 'Bagaimana cara membayar angsuran?',
          kunci: ['bayar angsuran', 'bayar cicilan', 'cara bayar', 'potong gaji', 'potong tunjangan', 'setor angsuran', 'membayar', 'dipotong', 'dari gaji', 'dari tunjangan', 'telat bayar', 'terlambat', 'telat', 'tunggakan', 'lewat jatuh tempo'],
          jawab: '💳 Tergantung metode pinjaman Anda:<br>• <strong>Potong tunjangan</strong>: dipotong otomatis oleh Bendahara Sekolah sesuai rencana potongan bulanan.<br>• <strong>Setor langsung</strong>: bayar di loket Bank Mini; angsuran dibayar <strong>berurutan</strong> dan Anda menerima <strong>bukti pembayaran</strong>.<br>Bila potongan bulan itu gagal, angsuran tersebut dapat dibayar langsung di loket.' },

        { id: 'pinjam-lagi', peran: ['nasabah_pegawai'], tanya: 'Bisakah saya mengajukan pinjaman baru?',
          kunci: ['pinjam lagi', 'pinjaman baru', 'tambah pinjaman', 'top up', 'pinjaman kedua', 'ajukan lagi'],
          jawab: '🔁 Pinjaman baru hanya bisa diajukan setelah pinjaman sebelumnya <strong>lunas</strong>. Selama masih ada pinjaman berjalan, form pengajuan menampilkan pemberitahuan dan pengajuan ditolak.' },

        { id: 'pelunasan', peran: ['nasabah_pegawai', 'bendahara_mini'], tanya: 'Bisakah melunasi pinjaman lebih cepat?',
          kunci: ['lunasi', 'melunasi', 'pelunasan', 'lunas lebih cepat', 'percepat pelunasan', 'bayar sekaligus', 'lunas sekarang', 'sekaligus'],
          jawab: '🏁 Saat ini pelunasan dilakukan dengan membayar angsuran tersisa <strong>secara berurutan</strong> di loket Bank Mini. Setelah angsuran terakhir dibayar, status pinjaman otomatis menjadi <strong>Lunas</strong> dan sisa piutang Rp0.' },

        { id: 'pinjaman-ditolak', peran: ['nasabah_pegawai'], tanya: 'Kenapa pinjaman saya ditolak?',
          kunci: ['ditolak', 'tidak layak', 'kenapa ditolak', 'pinjaman ditolak', 'alasan ditolak', 'tidak disetujui'],
          jawab: '❌ Pinjaman ditolak bila hasil penilaian <strong>tidak layak</strong>, biasanya karena cicilan melebihi 40% penghasilan. Catatan penilaian dapat dilihat di <strong>Info Pinjaman</strong>; Anda dapat mengajukan kembali dengan plafon lebih kecil atau tenor lebih panjang.' },

        { id: 'ubah-metode-nasabah', peran: ['nasabah_pegawai'], tanya: 'Bisakah mengganti metode pembayaran angsuran?',
          kunci: ['ganti metode', 'ubah metode', 'pindah metode', 'salah metode', 'ganti cara bayar'],
          jawab: '🔄 Bisa. Hubungi <strong>Bendahara Bank Mini</strong>; metode dapat diubah antara potong tunjangan dan setor langsung selama tidak ada angsuran yang sedang diproses dalam paket potongan bulan itu. Riwayat angsuran tetap di kartu piutang yang sama.' },

        // ───────────────────────── NASABAH SISWA ─────────────────────────
        { id: 'pinjaman-siswa', peran: ['nasabah_siswa'], tanya: 'Bisakah siswa meminjam?',
          kunci: ['pinjaman', 'pinjam', 'kredit', 'utang', 'hutang', 'meminjam', 'ajukan pinjaman'],
          jawab: 'ℹ️ Fasilitas pinjaman <strong>khusus guru dan karyawan</strong>. Rekening siswa dapat digunakan untuk <strong>menabung</strong>, menarik tabungan, dan memantau mutasi serta jasa tabungan.' },

        // ───────────────────────── SISWA PRAKTIK ─────────────────────────
        { id: 'draft', peran: ['siswa_praktik'], tanya: 'Cara input draft setoran/penarikan?',
          kunci: ['input draft', 'buat draft', 'entri setoran', 'entri penarikan', 'catat transaksi', 'input transaksi', 'cara input'],
          umum: ['draft', 'entri', 'setor', 'setoran', 'tarik', 'penarikan'],
          jawab: '📝 <strong>Input draft:</strong><br>1. Buka <strong>Input Draft</strong> (atau Entri Setoran / Entri Penarikan)<br>2. Cari rekening nasabah, pilih jenis & isi nominal <strong>Rp1.000 – Rp5.000.000</strong><br>3. Simpan → status <strong>pending</strong> sampai diverifikasi Teller<br>Saldo nasabah baru berubah setelah draft <strong>disetujui</strong>.',
          menu: [['Input Draft', '/tabungan/draft']] },

        { id: 'status-draft', peran: ['siswa_praktik'], tanya: 'Bagaimana status draft saya?',
          kunci: ['status draft', 'draft ditolak', 'draft disetujui', 'riwayat draft', 'sudah diverifikasi', 'ditolak', 'disetujui'],
          jawab: '📂 Lihat <strong>Riwayat Draft</strong>: status <strong>Pending</strong> (menunggu Teller), <strong>Disetujui</strong> (transaksi tercatat ke rekening), atau <strong>Ditolak</strong>. Draft yang ditolak dapat diinput ulang dengan data yang benar.',
          menu: [['Riwayat Draft', '/tabungan/draft/riwayat']] },

        { id: 'buka-rekening-petugas', peran: ['siswa_praktik', 'teller'], tanya: 'Cara membuka / mengaktifkan rekening nasabah?',
          kunci: ['buka rekening', 'rekening baru', 'daftarkan nasabah', 'aktivasi rekening', 'aktifkan rekening', 'setoran awal', 'nasabah baru'],
          jawab: '🆕 Buka menu <strong>Buka Rekening</strong>: isi data nasabah (nama, nomor induk, kelas/unit, nomor HP) atau pilih pendaftar berstatus menunggu, lalu terima <strong>setoran awal minimal Rp10.000</strong> untuk mengaktifkan rekening. Nomor rekening dibuat otomatis.',
          menu: [['Buka Rekening', '/tabungan/buka-rekening']] },

        // ───────────────────────── TELLER ─────────────────────────
        { id: 'verifikasi-draft', peran: ['teller'], tanya: 'Cara verifikasi draft siswa praktik?',
          kunci: ['verifikasi', 'verifikasi draft', 'setujui draft', 'tolak draft', 'approve', 'draft'],
          jawab: '✔️ Buka <strong>Verifikasi Draft</strong>, periksa rincian lalu pilih <strong>Setujui</strong> (transaksi dibukukan, saldo & kas berubah, bukti dibuat) atau <strong>Tolak</strong>. Draft pending di bulan berjalan menghalangi tutup periode, jadi segera diproses.',
          menu: [['Verifikasi Draft', '/tabungan/verifikasi']] },

        { id: 'setor-teller', peran: ['teller'], tanya: 'Cara mencatat setoran?',
          kunci: ['catat setoran', 'terima setoran', 'cara setor', 'mencatat setoran'],
          umum: ['setor', 'setoran'],
          jawab: '🏦 Menu <strong>Setoran</strong>: pilih rekening aktif, isi nominal (minimal <strong>Rp1.000</strong>), klik <strong>Simpan Setoran</strong>. Saldo rekening & kas tunai otomatis bertambah dan bukti setoran langsung tampil.',
          menu: [['Setoran', '/tabungan/setoran']] },

        { id: 'tarik-teller', peran: ['teller'], tanya: 'Cara mencatat penarikan?',
          kunci: ['catat penarikan', 'cara tarik', 'mencatat penarikan', 'tarik tunai', 'ambil uang', 'mau ambil', 'ambil tabungan'],
          umum: ['tarik', 'penarikan'],
          jawab: '💸 Menu <strong>Penarikan</strong>: pilih rekening, isi nominal. Penarikan ditolak bila saldo tersisa kurang dari <strong>Rp10.000</strong> atau kas tunai tidak mencukupi.',
          menu: [['Penarikan', '/tabungan/penarikan']] },

        { id: 'cetak-teller', peran: ['teller'], tanya: 'Cara mencetak bukti / mutasi?',
          kunci: ['cetak', 'print', 'slip', 'struk', 'cetak bukti', 'cetak mutasi', 'dicetak', 'bisa dicetak'],
          umum: ['bukti'],
          jawab: '🖨️ • <strong>Cetak Bukti</strong>: bukti setiap setoran/penarikan<br>• <strong>Saldo & Mutasi</strong> → tombol cetak mutasi rekening<br>• <strong>Log Transaksi</strong> → cetak rekap harian',
          menu: [['Cetak Bukti', '/tabungan/bukti']] },

        { id: 'koreksi-teller', peran: ['teller'], tanya: 'Cara koreksi transaksi yang salah input?',
          kunci: ['koreksi', 'salah input', 'batal transaksi', 'batalkan transaksi', 'reversal', 'transaksi salah', 'batal', 'membatalkan'],
          jawab: '↩️ Menu <strong>Koreksi Transaksi</strong>: pilih transaksi, isi alasan. Sistem membuat <strong>transaksi pembalik</strong> sehingga saldo & kas kembali benar, dan transaksi asal tetap tercatat untuk audit.',
          menu: [['Koreksi Transaksi', '/tabungan/koreksi']] },

        { id: 'jasa-teller', peran: ['teller'], tanya: 'Bagaimana jasa tabungan dihitung?',
          kunci: ['hitung jasa', 'jasa tabungan', 'bunga tabungan', 'jasa', 'bunga'],
          jawab: '📈 Menu <strong>Hitung Jasa</strong> menampilkan perhitungan jasa per rekening: siswa <strong>0,1%</strong>, pegawai <strong>0,2%</strong> per bulan. Jasa dibukukan otomatis setiap akhir bulan pukul 23.00 (memerlukan penjadwal server aktif) dan hanya pada periode yang masih terbuka.',
          menu: [['Hitung Jasa', '/tabungan/jasa']] },

        { id: 'mutasi-teller', peran: ['teller'], tanya: 'Cara melihat saldo & log transaksi?',
          kunci: ['saldo nasabah', 'cek saldo', 'mutasi', 'log transaksi', 'rekap harian', 'riwayat transaksi', 'saldo', 'rekap', 'hari ini', 'rekap transaksi'],
          jawab: '📒 • <strong>Saldo & Mutasi</strong>: cari rekening untuk melihat saldo dan riwayatnya<br>• <strong>Log Transaksi</strong>: rekap transaksi harian loket',
          menu: [['Saldo & Mutasi', '/tabungan/saldo'], ['Log Transaksi', '/tabungan/log-transaksi']] },

        // ───────────────────────── BENDAHARA SEKOLAH ─────────────────────────
        { id: 'rencana-sekolah', peran: ['bendahara'], tanya: 'Di mana daftar rencana potongan?',
          kunci: ['rencana potongan', 'paket potongan', 'lihat potongan', 'lihat paket', 'harus dipotong', 'yang dipotong', 'siapa dipotong'],
          umum: ['daftar potongan', 'potongan', 'paket'],
          jawab: '📋 Menu <strong>Rencana & Hasil Potongan</strong> berisi paket dari Bendahara Bank Mini. Klik <strong>ikon mata</strong> untuk melihat rincian pegawai, angsuran ke-berapa, dan nominal yang harus dipotong.',
          menu: [['Rencana & Hasil Potongan', '/bendahara/potongan']] },

        { id: 'unduh-sekolah', peran: ['bendahara'], tanya: 'Cara mengunduh daftar potongan?',
          kunci: ['unduh', 'download', 'csv', 'excel', 'file potongan', 'format payroll', 'download daftar', 'unduh daftar', 'mengunduh'],
          jawab: '⬇️ Pada baris paket, klik <strong>ikon unduh</strong> untuk file CSV format payroll (nama, unit, gaji, tunjangan, pokok, jasa, total potongan) yang isinya sama dengan rincian di layar.' },

        { id: 'catat-hasil', peran: ['bendahara'], tanya: 'Cara mencatat hasil potongan?',
          kunci: ['catat hasil', 'hasil potongan', 'terpotong', 'tidak terpotong', 'potongan gagal', 'gagal', 'berhasil', 'simpan hasil', 'bukti transfer', 'unggah', 'unggah bukti', 'serahkan dana'],
          jawab: '✍️ Buka paket (ikon mata), lalu:<br>1. Tandai tiap pegawai <strong>Berhasil</strong> atau <strong>Gagal</strong> (isi keterangan bila gagal)<br>2. Isi tanggal potongan & metode penyerahan (<strong>tunai</strong> / <strong>transfer</strong> + unggah bukti)<br>3. Klik <strong>Simpan & Serahkan ke Bank Mini</strong><br>Angsuran resmi tercatat setelah Bendahara Bank Mini mengonfirmasi penerimaan dana.' },

        { id: 'status-paket', peran: ['bendahara', 'bendahara_mini'], tanya: 'Apa arti status paket potongan?',
          kunci: ['status paket', 'menunggu verifikasi', 'dikirim', 'selesai', 'arti status'],
          jawab: '🔖 <strong>Dikirim</strong>: paket baru dari Bank Mini, menunggu dicatat Bendahara Sekolah → <strong>Menunggu Verifikasi</strong>: hasil sudah dicatat & dana diserahkan → <strong>Selesai</strong>: Bank Mini menerima dana, angsuran dibukukan dan kas bertambah.' },

        { id: 'bast', peran: ['bendahara', 'bendahara_mini'], tanya: 'Apa itu BAST dan cara mencetaknya?',
          kunci: ['bast', 'berita acara', 'serah terima', 'cetak bast'],
          jawab: '📄 <strong>BAST</strong> (Berita Acara Serah Terima) mendokumentasikan penyerahan dana potongan dari Bendahara Sekolah ke Bank Mini. Tombol <strong>Cetak BAST</strong> muncul setelah hasil potongan dicatat (status Menunggu Verifikasi / Selesai).' },

        { id: 'tunjangan', peran: ['bendahara'], tanya: 'Bagaimana memperbarui data gaji/tunjangan pegawai?',
          kunci: ['tunjangan', 'data gaji', 'gaji pegawai', 'data tunjangan', 'update gaji'],
          jawab: '🧾 Menu <strong>Data Tunjangan</strong>: pilih bulan, isi gaji pokok, tunjangan, dan <strong>potongan lain</strong> (mis. pajak) tiap pegawai, lalu <strong>Simpan</strong>. Bisa juga <strong>Salin dari Bulan Lalu</strong> atau <strong>Impor CSV</strong> (unduh templatenya dulu). Kolom <em>tunjangan bersih</em> langsung menunjukkan pegawai yang tunjangannya tidak cukup untuk dipotong angsuran. Data ini dipakai Bendahara Bank Mini saat menilai kelayakan dan menyusun rencana potongan.',
          menu: [['Data Tunjangan', '/bendahara/tunjangan']] },

        // ───────────────────────── BENDAHARA BANK MINI ─────────────────────────
        { id: 'pengajuan-bbm', peran: ['bendahara_mini'], tanya: 'Cara menginput pengajuan pinjaman pegawai?',
          kunci: ['ajukan pinjaman', 'pengajuan pinjaman', 'input pinjaman', 'pinjaman baru', 'buat pinjaman', 'mau pinjam', 'ingin pinjam', 'pegawai pinjam'],
          umum: ['pengajuan', 'daftar pinjaman', 'pinjaman', 'pinjam'],
          jawab: '📋 Menu <strong>Pengajuan Pinjaman</strong> (atau tombol <strong>Ajukan Pinjaman</strong> di Daftar Pinjaman):<br>1. Pilih pegawai (hanya yang rekeningnya aktif & tanpa pinjaman berjalan)<br>2. Isi plafon (min Rp100.000), tenor (1–60 bulan), metode bayar<br>3. Simpan → lanjut ke <strong>Penilaian Kelayakan</strong>',
          menu: [['Pengajuan Pinjaman', '/bbm/pinjaman/ajukan'], ['Daftar Pinjaman', '/bbm/pinjaman']] },

        { id: 'kelayakan', peran: ['bendahara_mini'], tanya: 'Cara menilai kelayakan pinjaman?',
          kunci: ['kelayakan', 'penilaian', 'nilai pinjaman', 'layak', 'tidak layak', 'dsr', 'tolak pinjaman', 'setujui pinjaman'],
          jawab: '⚖️ Menu <strong>Penilaian Kelayakan</strong> → <strong>Beri Penilaian</strong>: periksa penghasilan (gaji + tunjangan) dan indikator rasio cicilan (aman ≤30%, batas 40%), pilih <strong>Layak</strong> / <strong>Tidak Layak</strong>, isi catatan, lalu <strong>Simpan Keputusan</strong>. Layak → siap dicairkan; Tidak Layak → ditolak.',
          menu: [['Penilaian Kelayakan', '/bbm/pinjaman/kelayakan']] },

        { id: 'pencairan', peran: ['bendahara_mini'], tanya: 'Cara mencairkan pinjaman?',
          kunci: ['cair', 'cairkan', 'pencairan', 'serahkan dana', 'mencairkan', 'kas tidak cukup', 'tidak cukup', 'saldo tidak cukup', 'belum cair', 'sudah layak'],
          jawab: '💵 Menu <strong>Pencairan Pinjaman</strong> → <strong>Cairkan Dana</strong> (hanya pinjaman berstatus Layak). Pilih sumber dana <strong>tunai</strong> (kas tunai) atau <strong>transfer</strong> (kas bank) — saldo kas harus cukup. Setelah dikonfirmasi, kas berkurang dan <strong>jadwal angsuran</strong> terbentuk otomatis.',
          menu: [['Pencairan Pinjaman', '/bbm/pinjaman/cair']] },

        { id: 'angsuran-langsung', peran: ['bendahara_mini'], tanya: 'Cara menerima pembayaran angsuran langsung?',
          kunci: ['angsuran langsung', 'bayar angsuran', 'terima angsuran', 'catat angsuran', 'pembayaran angsuran', 'loket'],
          umum: ['angsuran', 'pokok'],
          jawab: '💳 Menu <strong>Angsuran Langsung</strong> → <strong>Bayar Ke-n</strong> → <strong>Konfirmasi Terima Kas</strong>. Nominal mengikuti jadwal, angsuran wajib <strong>berurutan</strong>, dan pokok tidak boleh melebihi sisa piutang. Setelah tersimpan, klik <strong>Cetak Bukti Pembayaran</strong>.',
          menu: [['Angsuran Langsung', '/bbm/angsuran/langsung']] },

        { id: 'paket-terkunci', peran: ['bendahara_mini'], tanya: 'Kenapa tombol bayar tidak muncul (Dalam Paket Potongan)?',
          kunci: ['dalam paket potongan', 'tombol bayar hilang', 'tidak bisa bayar', 'bayar tidak muncul', 'paket potongan', 'pembayaran ganda'],
          jawab: '🔒 Angsuran itu sedang diproses dalam <strong>paket potongan tunjangan</strong>, jadi tidak bisa dibayar langsung agar tidak terjadi pembayaran ganda. Tunggu hasil dari Bendahara Sekolah: bila potongan <strong>gagal</strong>, tombol bayar langsung otomatis muncul kembali.' },

        { id: 'rencana-bbm', peran: ['bendahara_mini'], tanya: 'Cara menyusun rencana potongan bulanan?',
          kunci: ['rencana potongan', 'susun potongan', 'susun rencana', 'terbitkan rencana', 'potongan gaji', 'potong gaji', 'potongan tunjangan'],
          jawab: '🗓️ Menu <strong>Rencana Potongan</strong>: pilih bulan & tahun, centang angsuran pegawai bermetode potong gaji yang jatuh tempo, lalu <strong>Terbitkan Rencana ke Bendahara Sekolah</strong>. Hanya angsuran berurutan yang belum dibayar yang dapat masuk paket.',
          menu: [['Rencana Potongan', '/bbm/angsuran/potongan']] },

        { id: 'konfirmasi-dana', peran: ['bendahara_mini'], tanya: 'Cara mengonfirmasi dana potongan dari sekolah?',
          kunci: ['konfirmasi', 'terima uang', 'terima dana', 'verifikasi potongan', 'bukukan', 'bukukan kas', 'dana potongan', 'uang potongan', 'sudah diterima', 'diterima'],
          jawab: '📥 Di <strong>Rencana Potongan</strong>, buka paket berstatus <strong>Menunggu Verifikasi</strong> (ikon mata), periksa bukti transfer/serah terima, isi tanggal terima, lalu <strong>Terima Uang & Bukukan Kas</strong>. Angsuran yang berhasil dipotong tercatat dan kas bertambah.',
          menu: [['Rencana Potongan', '/bbm/angsuran/potongan']] },

        { id: 'potongan-gagal-bbm', peran: ['bendahara_mini'], tanya: 'Apa yang dilakukan bila potongan gagal?',
          kunci: ['potongan gagal', 'gagal potong', 'tidak terpotong', 'gagal dipotong'],
          jawab: '🔁 Angsuran yang ditandai <strong>gagal</strong> oleh Bendahara Sekolah tidak dibukukan dan statusnya tetap <strong>Belum</strong>. Angsuran itu dapat dibayar langsung lewat <strong>Angsuran Langsung</strong>; tercatat di kartu piutang yang sama dengan metode <em>Langsung</em>.' },

        { id: 'koreksi-angsuran', peran: ['bendahara_mini'], tanya: 'Cara koreksi angsuran yang salah input?',
          kunci: ['koreksi angsuran', 'koreksi', 'salah input', 'batal angsuran', 'ganti angsuran', 'salah masukin', 'reversal'],
          jawab: '↩️ Menu <strong>Koreksi Angsuran</strong>: pilih transaksi, tentukan tipe <strong>Ganti</strong> (catat ulang dengan nominal/rekening benar) atau <strong>Batal</strong>, dan isi alasan. Sistem membuat mutasi kas pembalik; riwayat lama tetap ada untuk audit.',
          menu: [['Koreksi Angsuran', '/bbm/angsuran/koreksi']] },

        { id: 'kartu-piutang', peran: ['bendahara_mini'], tanya: 'Di mana melihat sisa piutang pegawai?',
          kunci: ['kartu piutang', 'sisa piutang', 'rekap piutang', 'piutang', 'sisa pinjaman', 'jadwal angsuran'],
          jawab: '🗂️ Menu <strong>Kartu Piutang</strong>: galeri kartu tiap peminjam (sisa pokok & progres). Klik kartu untuk jadwal lengkap, riwayat pembayaran (dengan metode Potong Tunjangan / Langsung), dan tombol <strong>Cetak Kartu</strong>.',
          menu: [['Kartu Piutang', '/bbm/kartu']] },

        { id: 'kas-bbm', peran: ['bendahara_mini'], tanya: 'Cara mencatat & melihat kas tunai / bank?',
          kunci: ['kas tunai', 'kas bank', 'mutasi kas', 'saldo kas', 'catat kas', 'kas masuk', 'kas keluar', 'kas'],
          jawab: '🏧 • <strong>Kas Tunai (Cash)</strong>: uang fisik di brankas<br>• <strong>Kas Bank Bendahara</strong>: rekening bank milik Bank Mini<br>Gunakan filter <strong>Per Hari / Bulan / Tahun / Semua</strong>. Mutasi di luar transaksi otomatis dicatat lewat <strong>Catat Mutasi Manual</strong> (minimal Rp1.000, keterangan wajib; kas keluar tidak boleh melebihi saldo).',
          menu: [['Kas Tunai', '/kas/harian'], ['Kas Bank', '/kas/bank']] },

        { id: 'cetak-bbm', peran: ['bendahara_mini'], tanya: 'Apa saja yang bisa dicetak?',
          kunci: ['cetak', 'print', 'cetak bukti', 'cetak laporan', 'cetak kartu', 'struk', 'dicetak', 'bisa dicetak', 'print kartu', 'print bukti', 'print laporan'],
          umum: ['bukti', 'laporan'],
          jawab: '🖨️ • <strong>Bukti pembayaran angsuran</strong>: setelah bayar, atau tombol Cetak di riwayat Angsuran Langsung<br>• <strong>Kartu piutang</strong>: tombol Cetak Kartu di Kartu Piutang<br>• <strong>Laporan kas tunai / bank</strong>: tombol Cetak Laporan (mengikuti filter periode)<br>• <strong>BAST</strong> potongan: di rincian paket Rencana Potongan' },

        { id: 'ubah-metode-bbm', peran: ['bendahara_mini'], tanya: 'Cara mengubah metode pembayaran pinjaman?',
          kunci: ['ubah metode', 'ganti metode', 'salah metode', 'pindah metode', 'metode bayar', 'salah pilih metode'],
          jawab: '🔄 Di <strong>Daftar Pinjaman</strong>, klik <strong>Ubah metode</strong> pada pinjaman (status diajukan/layak/cair), pilih metode baru dan isi alasan. Perubahan ditolak selama masih ada angsuran dalam paket potongan yang diproses; setiap perubahan tercatat di log audit.',
          menu: [['Daftar Pinjaman', '/bbm/pinjaman']] },

        { id: 'pinjaman-aktif-bbm', peran: ['bendahara_mini'], tanya: 'Kenapa pegawai tidak bisa diajukan pinjaman?',
          kunci: ['tidak bisa diajukan', 'pinjaman aktif', 'sudah punya pinjaman', 'pegawai tidak muncul', 'nama tidak ada'],
          jawab: 'ℹ️ Pegawai hanya muncul/bisa diajukan bila <strong>rekeningnya aktif</strong> dan <strong>tidak memiliki pinjaman berjalan</strong> (diajukan, layak, atau cair). Pinjaman baru dapat diajukan setelah pinjaman lama lunas.' },

        // ───────────────────────── MANAJER ─────────────────────────
        { id: 'dashboard-manajer', peran: ['manager'], tanya: 'Apa isi dashboard manajer?',
          kunci: ['dashboard', 'ringkasan', 'dpk', 'dana pihak ketiga', 'posisi kas', 'kesehatan bank'],
          jawab: '📊 Dashboard menampilkan total tabungan (DPK), portofolio pinjaman disalurkan, kas brankas, jumlah nasabah aktif, transaksi tabungan terbesar, rasio likuiditas, dan status periode berjalan.',
          menu: [['Dashboard', '/dashboard']] },

        { id: 'laporan-tabungan', peran: ['manager'], tanya: 'Di mana laporan tabungan?',
          kunci: ['laporan tabungan', 'rekap tabungan', 'tabungan bulanan', 'kas harian tabungan', 'laporan simpanan'],
          jawab: '📗 Menu <strong>Laporan Tabungan</strong> memiliki tab saldo per nasabah, rekap bulanan, dan kas harian tabungan. Pilih kategori (siswa / guru) dan bulan; data otomatis dari transaksi tanpa input ulang.',
          menu: [['Laporan Tabungan', '/laporan/tabungan']] },

        { id: 'laporan-pinjaman', peran: ['manager'], tanya: 'Di mana laporan pinjaman & tunggakan?',
          kunci: ['laporan pinjaman', 'kolektibilitas', 'tunggakan', 'npl', 'kredit macet', 'piutang', 'kas bendahara'],
          jawab: '📕 Menu <strong>Laporan Pinjaman</strong>: posisi tiap pinjaman (sisa pokok, tunggakan, hari terlambat) dengan <strong>kolektibilitas standar OJK</strong>, serta tab kas bendahara.',
          menu: [['Laporan Pinjaman', '/laporan/pinjaman']] },

        { id: 'neraca', peran: ['manager'], tanya: 'Bagaimana membaca neraca?',
          kunci: ['neraca', 'gabungan', 'aktiva', 'pasiva', 'ekuitas', 'pos manual', 'laporan gabungan'],
          jawab: '⚖️ Menu <strong>Neraca & Gabungan</strong>: aktiva (kas tunai, kas bank, piutang pinjaman) dan pasiva (tabungan siswa, tabungan guru/karyawan, jasa terutang, ekuitas) per akhir bulan. Pos yang tidak tercatat otomatis (deposito, peralatan, modal) ditambahkan sebagai <strong>pos manual</strong>.',
          menu: [['Neraca & Gabungan', '/laporan/neraca']] },

        { id: 'tahunan', peran: ['manager'], tanya: 'Di mana laporan tahunan / laba rugi?',
          kunci: ['laporan tahunan', 'tahunan', 'laba rugi', 'shu', 'pendapatan jasa'],
          jawab: '📘 Menu <strong>Laporan Tahunan</strong> merangkum pendapatan jasa pinjaman, beban jasa tabungan, dan laba per bulan dalam satu tahun.',
          menu: [['Laporan Tahunan', '/laporan/tahunan']] },

        { id: 'ekspor', peran: ['manager'], tanya: 'Cara ekspor laporan ke PDF/Excel?',
          kunci: ['ekspor', 'export', 'pdf', 'excel', 'xlsx', 'unduh laporan', 'download laporan', 'csv'],
          jawab: '⬇️ Setiap laporan memiliki tombol <strong>Unduh</strong>: <strong>Excel</strong>, <strong>PDF</strong>, dan <strong>CSV</strong>, berisi data sesuai periode yang sedang tampil. Menu <strong>Ekspor Dokumen</strong> merangkum semua unduhan di satu tempat.',
          menu: [['Ekspor Dokumen', '/laporan/ekspor']] },

        { id: 'tutup-periode', peran: ['manager'], tanya: 'Cara menutup periode bulanan?',
          kunci: ['tutup periode', 'tutup buku', 'kunci periode', 'menutup periode', 'closing', 'belum ditutup', 'ditutup', 'bulan lalu'],
          jawab: '🔐 Menu <strong>Tutup Periode</strong>: pilih bulan, isi catatan, centang pernyataan pemeriksaan, lalu <strong>Tutup Periode</strong>. Syarat: bulan sudah berakhir, ditutup <strong>berurutan</strong> dari bulan terlama, dan tidak ada draft siswa yang masih pending. Setelah ditutup, transaksi bertanggal bulan itu terkunci.',
          menu: [['Tutup Periode', '/periode/tutup']] },

        { id: 'buka-periode', peran: ['manager'], tanya: 'Cara membuka kembali periode yang sudah ditutup?',
          kunci: ['buka kembali', 'buka periode', 'batal tutup', 'membuka periode', 'periode dibuka'],
          jawab: '🔓 Di <strong>Tutup Periode</strong>, pilih periode tertutup <strong>paling akhir</strong>, isi alasan (minimal 10 karakter), lalu <strong>Buka Kembali</strong>. Tindakan ini tercatat di log audit; tutup lagi setelah koreksi selesai.',
          menu: [['Tutup Periode', '/periode/tutup']] },

        { id: 'kelola-pengguna', peran: ['manager'], tanya: 'Cara menambah akun petugas?',
          kunci: ['kelola pengguna', 'tambah akun', 'buat akun', 'akun baru', 'user baru', 'tambah pengguna', 'pengguna baru', 'akun untuk', 'akun petugas'],
          jawab: '👥 Menu <strong>Kelola Pengguna</strong> → form <strong>Tambah Akun Petugas</strong>: pilih peran (Manajer, Bendahara Bank Mini, Bendahara Sekolah, Teller, Siswa Praktik), isi nama, nomor HP, email (unik), dan kata sandi awal. Kode pegawai dibuat otomatis; Siswa Praktik memakai NISN 10 digit. Akun nasabah dibuat lewat pembukaan rekening.',
          menu: [['Kelola Pengguna', '/akun/kelola']] },

        { id: 'status-akun', peran: ['manager'], tanya: 'Cara menonaktifkan / mengubah peran akun?',
          kunci: ['nonaktifkan', 'aktifkan', 'ubah peran', 'ganti peran', 'ganti role', 'ubah role', 'reset sandi pengguna', 'reset sandi', 'blokir akun', 'reset password'],
          jawab: '⚙️ Di <strong>Kelola Pengguna</strong>: tombol <strong>Ubah</strong> untuk mengganti peran/data/kata sandi (nasabah: <strong>Reset Sandi</strong>), dan <strong>Nonaktifkan / Aktifkan</strong> untuk status login. Anda tidak dapat menonaktifkan atau mengubah peran akun sendiri, dan minimal harus ada satu Manajer aktif.',
          menu: [['Kelola Pengguna', '/akun/kelola']] },
    ];

    // ───────────────────────── MESIN PENCOCOKAN ─────────────────────────

    // Bahasa sehari-hari / singkatan -> bentuk baku sebelum dicocokkan
    const GAUL = {
        gmn: 'bagaimana', gimana: 'bagaimana', gmna: 'bagaimana', bgmn: 'bagaimana', caranya: 'cara',
        gak: 'tidak', ga: 'tidak', gk: 'tidak', nggak: 'tidak', ngga: 'tidak', enggak: 'tidak', tdk: 'tidak', kagak: 'tidak',
        udah: 'sudah', udh: 'sudah', sdh: 'sudah', blm: 'belum', blom: 'belum',
        bikin: 'buat', bikinin: 'buat', dapet: 'dapat', dpt: 'dapat', dmn: 'di mana', dimana: 'di mana',
        upload: 'unggah', aplod: 'unggah', donlot: 'download', dl: 'download',
        pw: 'password', pass: 'password', passwd: 'password', sandinya: 'sandi',
        ngutang: 'utang', ngambil: 'ambil', nabung: 'nabung', duit: 'uang', yg: 'yang', utk: 'untuk', buat: 'buat',
        min: '', kak: '', bang: '', ya: '', dong: '', sih: '', deh: '', kok: '', nih: '',
        saldoku: 'saldo saya', tabunganku: 'tabungan saya', pinjamanku: 'pinjaman saya', rekeningku: 'rekening saya',
        aku: 'saya', gue: 'saya', gw: 'saya', kekunci: 'terkunci', kunci: 'terkunci', telad: 'telat',
        nyairin: 'cairkan', nyairkan: 'cairkan', dicairin: 'dicairkan', batalin: 'batalkan', batalinnya: 'batalkan', bayarin: 'bayar', ngajuin: 'ajukan', ajuin: 'ajukan',
    };

    function normalisasi(teks) {
        const kata = String(teks || '')
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, ' ')
            .split(/\s+/)
            .map(k => (Object.prototype.hasOwnProperty.call(GAUL, k) ? GAUL[k] : k))
            .filter(Boolean);
        return ' ' + kata.join(' ').replace(/\s+/g, ' ').trim() + ' ';
    }

    // Kata yang terlalu umum untuk dijadikan penanda kemiripan
    const KATA_HUBUNG = new Set(['yang', 'saya', 'untuk', 'dengan', 'bagaimana', 'cara', 'apa', 'berapa', 'bisa',
        'tidak', 'sudah', 'belum', 'mau', 'ingin', 'dari', 'kalau', 'kapan', 'kenapa', 'mana', 'ada', 'itu', 'ini',
        'bank', 'mini', 'sipintar', 'akan', 'apakah', 'atau', 'dan', 'juga', 'masih', 'lagi', 'sekarang']);

    function tokenBermakna(teksNorm) {
        return teksNorm.trim().split(' ').filter(k => k.length >= 4 && !KATA_HUBUNG.has(k));
    }

    function bolehUntuk(entri, peran) {
        return entri.peran.some(p =>
            p === 'semua' || p === peran ||
            (p === 'nasabah' && NASABAH.includes(peran)) ||
            (p === 'petugas' && PETUGAS.includes(peran)));
    }

    /**
     * Skor kecocokan: frasa utuh bernilai tinggi; kata tunggal harus utuh,
     * atau (bila >= 5 huruf) boleh berimbuhan, mis. "pinjamannya", "mencairkan".
     * Ini mencegah salah tangkap seperti "ok" di dalam "pokok".
     */
    function skor(entri, teksNorm, token) {
        let nilai = 0;
        for (const k of entri.kunci) {
            const kunci = k.trim();
            if (kunci.includes(' ')) {
                if (teksNorm.includes(' ' + kunci + ' ')) nilai += 3 + kunci.split(' ').length;
            } else if (token.includes(kunci)) {
                nilai += 2;
            } else if (kunci.length >= 5 && token.some(t => t.length > kunci.length && t.includes(kunci))) {
                nilai += 1.5;
            }
        }
        // Kata benda umum hanya menambah sedikit bobot
        for (const k of entri.umum || []) {
            const kunci = k.trim();
            if (kunci.includes(' ') ? teksNorm.includes(' ' + kunci + ' ') : token.includes(kunci)) {
                nilai += 1;
            } else if (!kunci.includes(' ') && kunci.length >= 5 && token.some(t => t.length > kunci.length && t.includes(kunci))) {
                nilai += 0.75;
            }
        }
        // Penguat ringan: kata bermakna yang juga muncul di contoh pertanyaan entri
        const kosakata = new Set(tokenBermakna(normalisasi(entri.tanya + ' ' + entri.kunci.join(' '))));
        const sama = tokenBermakna(teksNorm).filter(k => kosakata.has(k)).length;
        nilai += Math.min(sama, 3) * 0.3;

        // Jawaban khusus peran lebih diutamakan daripada jawaban umum
        if (nilai > 0 && !entri.peran.includes('semua')) nilai += 0.5;
        return nilai;
    }

    function peringkat(teks, peran) {
        const teksNorm = normalisasi(teks);
        const token = teksNorm.trim().split(' ');
        return FAQ.filter(e => bolehUntuk(e, peran))
            .map(e => ({ entri: e, nilai: skor(e, teksNorm, token) }))
            .filter(x => x.nilai > 0)
            .sort((a, b) => b.nilai - a.nilai);
    }

    function topikUntuk(peran) {
        return FAQ.filter(e => bolehUntuk(e, peran) && !['salam', 'terima-kasih', 'topik'].includes(e.id));
    }

    // Skor minimum agar dianggap cocok (1 kata kunci maksud, atau kata umum + bonus peran)
    const AMBANG = 1.5;

    window.SipintarFaq = {
        /** Jawaban terbaik atau null; disertai saran pertanyaan terkait. */
        cari(teks, peran) {
            const hasil = peringkat(teks, peran);
            if (!hasil.length || hasil[0].nilai < AMBANG) {
                return { entri: null, saran: hasil.slice(0, 3).map(x => x.entri) };
            }
            return { entri: hasil[0].entri, saran: hasil.slice(1, 3).filter(x => x.nilai >= AMBANG).map(x => x.entri) };
        },
        topik: topikUntuk,
        chipAwal(peran) {
            const pilihan = {
                manager: ['laporan-pinjaman', 'neraca', 'tutup-periode', 'kelola-pengguna', 'ekspor'],
                teller: ['verifikasi-draft', 'setor-teller', 'koreksi-teller', 'jasa-teller', 'cetak-teller'],
                siswa_praktik: ['draft', 'status-draft', 'buka-rekening-petugas'],
                bendahara: ['rencana-sekolah', 'catat-hasil', 'unduh-sekolah', 'bast'],
                bendahara_mini: ['pengajuan-bbm', 'kelayakan', 'angsuran-langsung', 'rencana-bbm', 'cetak-bbm'],
                nasabah_pegawai: ['saldo', 'ajukan-pinjaman', 'jadwal-angsuran', 'jasa-pinjaman', 'setor-nasabah'],
                nasabah_siswa: ['saldo', 'setor-nasabah', 'tarik-nasabah', 'jasa-tabungan', 'aktivasi'],
            }[peran] || ['login', 'kontak', 'jam'];
            return pilihan.map(id => FAQ.find(e => e.id === id)).filter(Boolean);
        },
        data: FAQ,
    };
})();
