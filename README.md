# Sistem Absensi Digital Peserta RAT — KPNI

Aplikasi web untuk mencatat kehadiran peserta Rapat Anggota Tahunan (RAT) di Koperasi Konsumen Pekerja PT NOK Indonesia (KPNI), Cikarang. Absensi dilakukan dengan tap kartu RFID atau ketik NIP, bisa disertai foto verifikasi dari kamera, dan hasilnya langsung terlihat di dashboard.

Dikembangkan oleh Ridzkal Jamil (NIM 312310048), Teknik Informatika Universitas Pelita Bangsa, sebagai proyek Kuliah Kerja Praktik (KKP) Juni–Agustus 2026.

## Daftar Isi

- [Fitur](#fitur)
- [Tech Stack](#tech-stack)
- [Prasyarat](#prasyarat)
- [Instalasi](#instalasi)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Alur Penggunaan](#alur-penggunaan)
- [Struktur Folder](#struktur-folder)
- [Database](#database)
- [Build Aset Front-end](#build-aset-front-end)
- [Data Pribadi](#data-pribadi)
- [Lisensi](#lisensi)

## Fitur

**Absensi**
- Input RFID (tap kartu) dan NIP manual dalam satu kolom; server yang menentukan apakah input itu NIP atau kartu.
- NIP yang kehilangan nol di depan karena Excel (12345 → 012345) dan UID kartu tanpa nol depan tetap dikenali.
- Peserta divalidasi per sesi: hanya yang terdaftar di sesi tersebut yang bisa absen.
- Satu kartu RFID cukup didaftarkan sekali dan berlaku untuk semua sesi dan RAT (kartu disimpan di data induk anggota).
- Verifikasi foto kamera (opsional) yang diambil otomatis saat absen.
- Notifikasi berhasil, sudah absen (beserta foto sebelumnya), tidak terdaftar, dan kartu belum terdaftar.
- Pembatalan absensi dalam 10 detik, hanya dari perangkat yang mencatatnya.
- Tap ganda / dua perangkat bersamaan tetap tercatat sekali (unique key per sesi + NIP).
- Tabel 5 absensi terakhir, suara konfirmasi, indikator koneksi, dan antrean offline yang otomatis tersinkron saat koneksi kembali. Gangguan server atau sesi login habis ditampilkan sebagai gagal, bukan disimpan diam-diam.
- Halaman absensi otomatis membuka sesi yang jamnya sedang berjalan.

**Dashboard**
- Kuorum dihitung per sesi (50% + 1 dari peserta sesi tersebut). Yang dihitung hanya kehadiran orang yang masih terdaftar sebagai peserta sesi itu.
- Kartu per sesi berisi jumlah hadir, persentase, progress bar, dan status kuorum.
- Ringkasan "X dari N sesi tercapai".
- Pembaruan otomatis setiap 5 detik tanpa memuat ulang halaman.

**Peserta RAT per sesi**
- Setiap sesi punya daftar peserta sendiri.
- Daftar peserta bisa diisi lewat import Excel, tambah manual (sekalian tap kartu RFID), atau salin dari kehadiran sesi sebelumnya (peserta sesi berikutnya = yang hadir di sesi sebelumnya).
- Daftar peserta sesi yang sudah dikunci dibekukan.
- Filter, pagination, export Excel, dan pendaftaran RFID massal.

**Kelola RAT & Sesi**
- RAT multi-tahun; RAT baru otomatis dibuatkan 5 sesi.
- Sesi bisa ditambah, diubah, dan dihapus.
- Jam sesi memakai format 24 jam.
- Sesi bisa dikunci dan dibuka kembali.
- RAT yang sudah punya data kehadiran tidak bisa dihapus; RAT yang ditandai selesai bisa diaktifkan lagi.

**Data Anggota (data induk)**
- Tambah, ubah, dan hapus data anggota.
- Import dan export Excel.
- Filter lengkap dan pendaftaran RFID massal.
- Anggota bisa dinonaktifkan atau diaktifkan kembali.

**Doorprize**
- Kelola hadiah beserta gambar.
- Daftar peserta eligible: hadir di **seluruh** sesi RAT yang dipakai (sesi yang punya daftar peserta).
- Satu orang hanya bisa menang sekali per RAT; undian dari dua laptop sekaligus tidak melebihi stok.
- Pemenang yang tidak hadir bisa ditandai **hangus**: unitnya diundi ulang, orangnya tidak ikut lagi.
- Random picker dengan animasi slot, suara, confetti, dan mode layar penuh untuk proyektor (tetap layar penuh di antara undian).
- Gambar hadiah otomatis diperkecil ke 800 px saat diunggah.
- Riwayat pemenang dan export Excel.

**Rekap**
- Excel per sesi (dengan peserta, kuorum, dan status), Excel gabungan + sheet Summary Doorprize, ZIP foto per sesi, cetak daftar hadir.
- Backup database dalam format SQL dan Excel.

**Keamanan**
- Login dengan role admin dan user (operator absensi). Akun dicek ulang ke database di setiap request, login dibatasi 5 percobaan per menit.
- Proteksi CSRF di semua form dan AJAX.
- Log aktivitas.
- File yang diunggah selalu disimpan ulang sebagai gambar JPG/PNG dengan nama acak, jadi tidak bisa berisi skrip.
- Password diganti → semua perangkat lain yang login dengan akun itu otomatis keluar.

## Tech Stack

| Bagian | Teknologi |
|---|---|
| Backend | CodeIgniter 4.7, PHP 8.2+ |
| Database | MySQL 8 / MariaDB 10.4+ |
| Front-end | Tailwind CSS 3 (sudah di-build), Vanilla JavaScript |
| Font & ikon | Poppins, JetBrains Mono, Tabler Icons — semua file lokal, tidak butuh internet |
| Export | PhpSpreadsheet, ZipArchive |
| Browser API | MediaDevices (kamera), Web Audio, View Transitions |

Aplikasi berjalan sepenuhnya di jaringan lokal. Saat hari-H RAT, laptop server cukup terhubung ke WiFi lokal tanpa internet.

## Prasyarat

- PHP 8.2+ dengan ekstensi `intl`, `mbstring`, `mysqli`, `gd`, `zip`
- MySQL / MariaDB (XAMPP sudah mencakup keduanya)
- Composer
- Node.js 18+ — hanya kalau ingin build ulang CSS/ikon, tidak diperlukan untuk menjalankan aplikasi
- RFID reader USB 125 kHz dan webcam (opsional)

## Instalasi

```bash
git clone https://github.com/ridzkaljamil/absensi-rat-kpni.git
cd absensi-rat-kpni
composer install

copy .env.example .env          # Linux/macOS: cp .env.example .env
```

Sesuaikan koneksi database di `.env`, buat database `absensi_rat_kpni`, lalu:

```bash
php spark migrate
php spark db:seed AdminSeeder            # akun admin / admin123
php spark db:seed AnggotaContohSeeder    # opsional: 30 anggota fiktif untuk mencoba
```

Segera ganti password admin lewat menu **Ganti Password** setelah login pertama.

## Menjalankan Aplikasi

Di Windows/XAMPP cukup klik dua kali **`Jalankan Aplikasi.bat`**: file ini memperbarui struktur database (`php spark migrate`), membuka browser, lalu menjalankan server untuk seluruh jaringan.

Cara manual:

```bash
php spark migrate
php spark serve --host 0.0.0.0 --port 8080
```

Buka `http://localhost:8080`, atau dari perangkat lain lewat IP laptop server.

**Catatan kinerja:** server bawaan PHP (`spark serve`) melayani satu permintaan pada satu waktu. Untuk absensi biasa ini cukup cepat, tetapi hindari mengunduh ZIP foto atau backup besar saat antrean absen sedang ramai. Untuk acara yang sangat besar, aplikasi bisa dijalankan lewat Apache XAMPP dengan document root ke folder `public/`.

**Memperbarui ke versi baru:** timpa file aplikasi, lalu jalankan `Jalankan Aplikasi.bat` (atau `php spark migrate`). Migration aman dijalankan berulang.

## Alur Penggunaan

**Persiapan**
1. Buat RAT baru di menu **RAT** (otomatis 5 sesi; atur nama dan jam di **Kelola Sesi**).
2. Import data anggota di **Data Anggota**.
3. Di **Peserta RAT**, pilih Sesi 1 lalu import daftar peserta.
4. Daftarkan kartu RFID (satu per satu atau lewat **RFID Massal**).

**Hari-H**
1. Buka **Absensi**, pilih sesi yang berjalan.
2. Peserta tap kartu atau operator mengetik NIP.
3. Setelah sesi selesai, kunci sesi di **Kelola Sesi**.
4. Untuk sesi berikutnya, buka **Peserta RAT**, pilih sesi itu, lalu klik **Salin … orang yang hadir** agar peserta sesi berikutnya diambil dari yang hadir di sesi sebelumnya.
5. Pantau kuorum tiap sesi di **Dashboard**.

**Doorprize**
1. Tambah hadiah di tab **Barang**.
2. Cek tab **Eligible** (hadir di seluruh sesi).
3. Undi di tab **Random Picker**, lalu export daftar pemenang.

## Struktur Folder

```
absensi-rat-kpni/
├── app/
│   ├── Config/            Routes, Filters (CSRF), Security, Pager, dll.
│   ├── Controllers/       BaseController (catatLog) + 11 controller modul
│   ├── Database/
│   │   ├── Migrations/    Skema tabel, termasuk peserta per sesi
│   │   └── Seeds/         AdminSeeder, AnggotaContohSeeder
│   ├── Filters/           AuthFilter (login + role, cek ulang akun ke database)
│   ├── Helpers/           kpni_helper.php (hitung_kuorum, label_sesi, normalisasi_nip, dll.)
│   ├── Libraries/         KartuRfid (aturan kartu RFID), ExcelExport (semua export Excel)
│   ├── Models/
│   └── Views/             layouts/main.php + view per modul
├── public/
│   ├── assets/            CSS, font, ikon hasil build, dan js/ (JavaScript per halaman)
│   ├── img/               Logo KPNI
│   └── uploads/           Foto absensi & gambar doorprize (tidak di-commit)
├── resources/css/         Sumber CSS Tailwind
├── tools/                 Script build aset
├── writable/              Cache, log, session
├── Jalankan Aplikasi.bat  Launcher Windows (migrate + serve)
├── .env.example
├── composer.json
└── package.json           Hanya untuk build aset
```

## Database

| Tabel | Isi |
|---|---|
| `tb_rat` | RAT per tahun buku |
| `tb_sesi` | Sesi per RAT (nama, jam, status kunci) |
| `tb_anggota` | Data induk anggota, termasuk kartu RFID (satu-satunya tempat kartu disimpan) |
| `tb_peserta_rat` | Peserta per sesi (`id_sesi`), unik per sesi + NIP |
| `tb_absensi` | Kehadiran per sesi, snapshot nama/NIP, foto; unik per sesi + NIP |
| `tb_doorprize_items` | Hadiah |
| `tb_doorprize_winners` | Pemenang (unik per RAT + NIP, kolom `hangus`) |
| `tb_users` | Akun login |
| `tb_log_aktivitas` | Log aktivitas |

Semua perubahan skema ada di `app/Database/Migrations`. Migration terakhir aman dijalankan ulang di database lama maupun baru.

## Build Aset Front-end

CSS (`public/assets/css/app.css`), daftar ikon (`icons.css`), dan font sudah ikut di repository, jadi langkah ini hanya perlu dilakukan kalau menambah class Tailwind atau ikon baru di view atau `public/assets/js`. Versi file aset (cache browser) diatur otomatis oleh helper `aset()`.

```bash
npm install
npm run build
```

## Data Pribadi

Data asli anggota dan peserta KPNI (Excel, dump database, seeder data asli), foto absensi, dan gambar upload **tidak** disimpan di repository; lihat `.gitignore`. Gunakan `AnggotaContohSeeder` untuk data percobaan.

## Lisensi

Dikembangkan sebagai tugas KKP di Universitas Pelita Bangsa dan digunakan oleh KPNI — Koperasi Konsumen Pekerja PT NOK Indonesia.

© 2026 Ridzkal Jamil
