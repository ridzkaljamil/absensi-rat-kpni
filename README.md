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
- Input RFID (tap kartu) dan NIP manual dalam satu kolom, metode terdeteksi otomatis.
- Peserta divalidasi per sesi: hanya yang terdaftar di sesi tersebut yang bisa absen.
- Satu kartu RFID cukup didaftarkan sekali dan berlaku untuk semua sesi.
- Verifikasi foto kamera (opsional) yang diambil otomatis saat absen.
- Notifikasi berhasil, sudah absen (beserta foto sebelumnya), tidak terdaftar, dan kartu belum terdaftar.
- Pembatalan absensi dalam 10 detik.
- Tabel 5 absensi terakhir, suara konfirmasi, indikator koneksi, dan antrean offline yang otomatis tersinkron saat koneksi kembali.

**Dashboard**
- Kuorum dihitung per sesi (50% + 1 dari peserta sesi tersebut).
- Kartu per sesi berisi jumlah hadir, persentase, progress bar, dan status kuorum.
- Ringkasan "X dari N sesi tercapai".
- Pembaruan otomatis setiap 5 detik tanpa memuat ulang halaman.

**Peserta RAT per sesi**
- Setiap sesi punya daftar peserta sendiri.
- Daftar peserta bisa diisi lewat import Excel, tambah manual, atau salin dari kehadiran sesi sebelumnya (peserta sesi berikutnya = yang hadir di sesi sebelumnya).
- Filter, pagination, export Excel, dan pendaftaran RFID massal.

**Kelola RAT & Sesi**
- RAT multi-tahun; RAT baru otomatis dibuatkan 5 sesi.
- Sesi bisa ditambah, diubah, dan dihapus.
- Jam sesi memakai format 24 jam.
- Sesi bisa dikunci dan dibuka kembali.

**Data Anggota (data induk)**
- Tambah, ubah, dan hapus data anggota.
- Import dan export Excel.
- Filter lengkap dan pendaftaran RFID massal.
- Anggota bisa dinonaktifkan atau diaktifkan kembali.

**Doorprize**
- Kelola hadiah beserta gambar.
- Daftar peserta eligible: hadir di **seluruh** sesi RAT.
- Random picker dengan animasi slot, suara, confetti, dan mode layar penuh untuk proyektor.
- Riwayat pemenang dan export Excel.

**Rekap**
- Excel per sesi (dengan peserta, kuorum, dan status), Excel gabungan + sheet Summary Doorprize, ZIP foto per sesi, cetak daftar hadir.
- Backup database dalam format SQL dan Excel.

**Keamanan**
- Login dengan role admin dan user (operator absensi).
- Proteksi CSRF di semua form dan AJAX.
- Log aktivitas.
- Folder upload tidak bisa menjalankan skrip.

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

```bash
php spark serve --port 8080
```

Buka `http://localhost:8080`. Untuk dipakai dari perangkat lain di jaringan yang sama, jalankan dengan `--host 0.0.0.0` lalu akses lewat IP laptop server.

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
│   ├── Helpers/           kpni_helper.php (hitung_kuorum, label_sesi, jam_valid)
│   ├── Models/
│   └── Views/             layouts/main.php + view per modul
├── public/
│   ├── assets/            CSS, font, dan ikon hasil build (di-commit)
│   ├── img/               Logo KPNI
│   └── uploads/           Foto absensi & gambar doorprize (tidak di-commit)
├── resources/css/         Sumber CSS Tailwind
├── tools/                 Script build aset
├── writable/              Cache, log, session
├── .env.example
├── composer.json
└── package.json           Hanya untuk build aset
```

## Database

| Tabel | Isi |
|---|---|
| `tb_rat` | RAT per tahun buku |
| `tb_sesi` | Sesi per RAT (nama, jam, status kunci) |
| `tb_anggota` | Data induk anggota |
| `tb_peserta_rat` | Peserta per sesi (`id_sesi`), unik per sesi + NIP |
| `tb_absensi` | Kehadiran per sesi, snapshot nama/NIP, foto |
| `tb_doorprize_items` | Hadiah |
| `tb_doorprize_winners` | Pemenang |
| `tb_users` | Akun login |
| `tb_log_aktivitas` | Log aktivitas |

Semua perubahan skema ada di `app/Database/Migrations`. Migration terakhir aman dijalankan ulang di database lama maupun baru.

## Build Aset Front-end

CSS (`public/assets/css/app.css`), daftar ikon (`icons.css`), dan font sudah ikut di repository, jadi langkah ini hanya perlu dilakukan kalau menambah class Tailwind atau ikon baru di view:

```bash
npm install
npm run build
```

## Data Pribadi

Data asli anggota dan peserta KPNI (Excel, dump database, seeder data asli), foto absensi, dan gambar upload **tidak** disimpan di repository; lihat `.gitignore`. Gunakan `AnggotaContohSeeder` untuk data percobaan.

## Lisensi

Dikembangkan sebagai tugas KKP di Universitas Pelita Bangsa dan digunakan oleh KPNI — Koperasi Konsumen Pekerja PT NOK Indonesia.

© 2026 Ridzkal Jamil
