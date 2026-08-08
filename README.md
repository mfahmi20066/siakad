# SIAKAD — SMA Negeri 4 Palopo

**Sistem Informasi Akademik** berbasis web untuk pengelolaan akademik sekolah: jadwal pelajaran, nilai, absensi, rapor online, pengumuman, prestasi, BK, ekstrakurikuler, chatbot AI, dan **SPMB Online** (Sistem Penerimaan Murid Baru).

> Proyek dibangun dengan **PHP native** (tanpa framework) dan **Bootstrap 5**.

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Struktur Folder](#struktur-folder)
- [Prasyarat](#prasyarat)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Migrasi Database](#migrasi-database)
- [Akun & Peran (Role)](#akun--peran-role)
- [Halaman Publik](#halaman-publik)
- [Modul SPMB Online](#modul-spmb-online)
- [Email & SMTP](#email--smtp)
- [Testing](#testing)
- [Keamanan](#keamanan)

---

## Fitur Utama

### 🎓 Akademik
- **Dashboard** terpisah untuk Admin, Guru, dan Siswa
- **Jadwal Pelajaran** per kelas, dikelompokkan per hari
- **Nilai** input & lihat nilai per mata pelajaran
- **Absensi** siswa
- **Rapor** online dengan cetak PDF (dompdf) + NISN & tanda tangan Wali Kelas
- **Tahun Ajaran** & semester berjalan
- **Mata Pelajaran**, **Kelas**, dan **Wali Kelas**

### 📋 Kesiswaan
- **Data Guru** & **Data Siswa** (dengan generator NIS berbasis NPSN)
- **Prestasi** siswa
- **BK / Pelanggaran**
- **Ekstrakurikuler**

### 🏫 SPMB Online (Penerimaan Murid Baru)
- Landing page SPMB publik (tanpa login)
- Pendaftaran online + upload dokumen
- Cek status & cetak bukti pendaftaran (PDF)
- Pengumuman hasil seleksi publik
- Panel admin: kelola **Gelombang** & **Jalur**, verifikasi dokumen, seleksi/ranking, finalisasi, ekspor Excel & PDF
- Akun SIAKAD siswa **dibuat otomatis** saat dinyatakan diterima

### 🌐 Website Publik
- Landing page (index.php): profil, sambutan Kepala Sekolah, berita, prestasi, statistik sekolah, galeri lightbox, kontak
- Kelola beranda, berita, pengumuman, dan galeri dari panel admin
- **Chatbot AI "SiA Bot"** dengan wawasan tentang sekolah, SPMB, akademik, dan FAQ

### 🔐 Lainnya
- Autentikasi & otorisasi per role (admin/guru/siswa)
- Registrasi akun + **Verifikasi Akun** oleh admin
- Lupa password via **OTP email**
- Notifikasi & pengumuman
- Laporan dengan ekspor **Excel & PDF** (kop resmi + tanda tangan)
- Alert modern (toast & modal) tanpa dependensi SweetAlert2

---

## Teknologi

| Komponen       | Teknologi                                             |
| -------------- | ----------------------------------------------------- |
| Bahasa         | PHP 5.1+ (native, tanpa framework)                    |
| Database       | MySQL 8.0+                                            |
| Frontend       | Bootstrap 5.3.0, jQuery, HTML5, CSS3, Font Poppins    |
| PDF            | dompdf ^3.1                                           |
| Email          | PHPMailer ^7.1                                        |
| Migrasi DB     | robmorgan/phinx 0.16                                  |
| Testing        | PHPUnit ^12.5                                         |

---

## Struktur Folder

```
siakad/
├── index.php           # Landing page publik
├── admin/              # Modul admin (dashboard, guru, siswa, nilai, rapor, spmb, dll)
├── guru/               # Modul guru
├── siswa/              # Modul siswa
├── auth/               # Login, logout, registrasi, lupa/reset password, OTP
├── spmb/               # SPMB publik (daftar, upload, cek status, cetak, pengumuman)
├── config/             # Koneksi, helper auth, mailer, secrets, chatbot, spmb_init
├── includes/           # Header, topbar, sidebar (per role), footer
├── assets/             # CSS, JS, font, icon, gambar
├── uploads/spmb/       # Dokumen SPMB (terproteksi .htaccess)
├── database/           # SQL dump & migrasi manual
├── migrations/         # Migrasi Phinx + seeds
├── tests/              # Unit test PHPUnit
├── cli/                # Skrip CLI (import NISN, finalisasi NIS, dll)
└── vendor/             # Composer packages
```

---

## Prasyarat

- PHP 7.4+ (disarankan 8.x) dengan ekstensi `mysqli`, `gd`, `fileinfo`, `mbstring`
- MySQL 8.0+
- [Composer](https://getcomposer.org/)
- Web server (disarankan **Laragon** / XAMPP)

---

## Instalasi

1. **Clone / salin project** ke folder web server, contoh:

   ```bash
   git clone <url-repo> D:\laragon\www\siakad
   ```

2. **Install dependensi Composer:**

   ```bash
   cd D:\laragon\www\siakad
   composer install
   ```

3. **Buat database** di phpMyAdmin/MySQL:

   ```sql
   CREATE DATABASE siakad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Import skema database:**

   Import file `database/siakad.sql` ke database `siakad`, atau jalankan migrasi Phinx:

   ```bash
   vendor/bin/phinx migrate -c phinx.php
   vendor/bin/phinx seed:run -c phinx.php
   ```

5. **Konfigurasi koneksi database:**

   Sesuaikan `config/koneksi.php`:

   ```php
   $host     = "localhost";
   $user     = "root";
   $password = "";      // isi password MySQL Anda
   $database = "siakad";
   ```

6. **Akses aplikasi:**

   - Landing page: `http://localhost/siakad/index.php`
   - Login: `http://localhost/siakad/auth/login.php`

---

## Konfigurasi

### Koneksi Database
- File: `config/koneksi.php` (mysqli — dipakai aplikasi web)
- File: `config/database.php` (PDO — dipakai service/CLI, mendukung `.env` opsional)
- Opsional: buat file `.env` di root untuk `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`

### Kredensial Rahasia (SMTP & API)
- File: `config/secrets.php` — **jangan di-commit ke repo publik** dan **jangan diekspos dari JavaScript**
- Berisi: `GROQ_API_KEY` (chatbot), `MAIL_*` (SMTP), `SCHOOL_NPSN` (generator NIS)

### Helper Auth
- `config/helper_auth.php` — `hashPassword()` & `checkPassword()` (bcrypt + fallback md5)

---

## Migrasi Database

- **Phinx:** file migrasi di `migrations/`, konfigurasi di `phinx.php`

  ```bash
  vendor/bin/phinx migrate -c phinx.php      # jalankan migrasi
  vendor/bin/phinx status -c phinx.php        # cek status
  ```

- **SQL manual:** `database/*.sql` (mis. `migration_agenda_hari_kategori.sql`)
- **Inisialisasi SPMB:** `config/spmb_init.php` (membuat tabel & kolom SPMB)

---

## Akun & Peran (Role)

Sistem memiliki **3 peran (role)**:

| Role   | Deskripsi                                    | Akses Utama                                  |
| ------ | -------------------------------------------- | -------------------------------------------- |
| Admin  | Pengelola utama sistem                       | Seluruh modul admin (master data, akademik, SPMB, website, laporan) |
| Guru   | Pengelola akademik kelas & penilaian         | Dashboard guru, nilai, absensi, jadwal       |
| Siswa  | Pengguna yang melihat akademik pribadinya    | Dashboard siswa, jadwal, nilai, rapor, absensi, pengumuman |

### Cara Mendapatkan Akun

Password default **tidak disertakan di dokumentasi ini demi keamanan**. Akun dibuat melalui salah satu cara berikut:

1. **Dibuat oleh admin** — akun di-generate/di-set dari menu *Pengguna* (`admin/users/`) atau saat menambah data guru/siswa.
2. **Registrasi mandiri** — melalui `auth/daftar.php`, kemudian akun harus diverifikasi oleh admin di menu *Verifikasi Akun*.
3. **Otomatis via SPMB** — akun siswa dibuat otomatis saat pendaftar SPMB dinyatakan **diterima** (status aktif).

> Hubungi **admin sekolah** untuk mendapatkan kredensial akun. Setelah login, segera ganti password melalui menu *Pengaturan Akun*.

---

## Halaman Publik

| Halaman                    | URL                                             |
| -------------------------- | ----------------------------------------------- |
| Landing page               | `/siakad/index.php`                             |
| Login                      | `/siakad/auth/login.php`                        |
| Registrasi akun            | `/siakad/auth/daftar.php`                       |
| Lupa password (OTP)        | `/siakad/auth/lupa-password.php`                |
| Landing SPMB               | `/siakad/spmb/index.php`                        |
| Daftar SPMB                | `/siakad/spmb/daftar.php`                       |
| Upload dokumen SPMB        | `/siakad/spmb/upload-dokumen.php`               |
| Cek status SPMB            | `/siakad/spmb/cek-status.php`                   |
| Cetak bukti SPMB (PDF)     | `/siakad/spmb/cetak-bukti.php`                  |
| Pengumuman hasil SPMB      | `/siakad/spmb/pengumuman.php`                   |

---

## Modul SPMB Online

### Alur untuk Calon Siswa (tanpa akun)
1. **Daftar** — isi biodata & pilih gelombang/jalur → dapat `no_pendaftaran`
2. **Upload dokumen** — verifikasi 2 faktor (no_pendaftaran + tanggal lahir)
3. **Cek status** — pantau timeline status pendaftaran
4. **Cetak bukti** — unduh bukti pendaftaran PDF
5. **Pengumuman** — lihat hasil seleksi secara publik

### Alur untuk Admin (`admin/spmb/`)
1. **Pengaturan** — toggle SPMB aktif, tanggal buka/tutup, syarat, link
2. **Kelola Gelombang & Jalur** — CRUD + kuota
3. **Data Pendaftar** — list, filter, search, detail
4. **Verifikasi dokumen** — tandai valid / tidak valid
5. **Seleksi** — ranking & set lolos seleksi
6. **Pengumuman** — finalisasi + auto-generate akun siswa
7. **Export** — Excel & PDF (kop resmi + tanda tangan)

### Struktur Data
- Tabel: `spmb_gelombang`, `spmb_jalur`, `spmb_pendaftar`, `spmb_dokumen`
- Kolom tambahan: `users.wajib_ganti_password`, `users.email`, `pengaturan.spmb_*`
- Dokumen disimpan di `uploads/spmb/{pendaftar_id}/` (terproteksi `.htaccess`)

---

## Email & SMTP

Semua email (OTP, notifikasi pendaftaran SPMB, verifikasi dokumen, hasil seleksi) dikirim via helper `kirimEmail()` di `config/mailer.php`.

1. Buka `config/secrets.php` lalu isi kredensial SMTP:
   - `MAIL_HOST` (contoh: `smtp.gmail.com`)
   - `MAIL_PORT` (`587` untuk TLS / `465` untuk SSL)
   - `MAIL_USERNAME` & `MAIL_PASSWORD`
   - `MAIL_SECURE` = `tls` atau `ssl`
2. Untuk Gmail: aktifkan *2-Step Verification* lalu buat **App Password** di [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).
3. Uji dengan fitur *Lupa Password* — OTP harus terkirim ke email.

---

## Testing

```bash
composer test
# alias: vendor/bin/phpunit --testdox tests
```

Unit test tersedia di `tests/` (contoh: `NisGeneratorServiceTest.php`).

### Skrip CLI
Skrip utilitas di `cli/`:
- `import_nisn.php` — import NISN
- `finalisasi_nis.php` — finalisasi NIS
- `hapus_siswa_sample.php` — hapus data siswa sampel
- `pisahkan_berita_pengumuman.php` — pisah tabel berita & pengumuman

---

## Keamanan

- Password di-hash menggunakan **bcrypt** (`hashPassword()` / `checkPassword()`)
- Semua input di-escape via `mysqli_real_escape_string()` / prepared statement
- Validasi upload dokumen: tipe file & ukuran maks **2MB**
- Folder `uploads/spmb/` diproteksi `.htaccess` (blok PHP execution + no directory listing)
- File `config/secrets.php` berisi kredensial rahasia — **jangan di-commit / di-ekspos**
- Access control per role (session) pada tiap modul
- SPMB publik menggunakan verifikasi 2 faktor (no_pendaftaran + tanggal lahir)

---

## Lisensi

Internal — milik **SMA Negeri 4 Palopo**. Dikembangkan untuk keperluan operasional sekolah.
