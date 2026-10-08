# PRD — Sistem Absensi Digital Peserta RAT KPNI

> Product Requirements Document
> Versi 2.0 — Agustus 2026

---

## 1. Ringkasan Produk

### 1.1 Tujuan
Mendigitalisasi proses pencatatan kehadiran peserta Rapat Anggota Tahunan (RAT) di KPNI — Koperasi Konsumen Pekerja PT NOK Indonesia, menggantikan sistem manual berbasis kertas tanda tangan.

### 1.2 Pengguna
- **Admin KPNI** — konfigurasi RAT, kelola data, undi doorprize
- **Operator** — input absensi saat hari-H menggunakan RFID reader + kamera

### 1.3 Scope
Aplikasi web lokal (LAN) yang berjalan di laptop operator, diakses via browser. Tidak memerlukan internet.

---

## 2. Fitur

### 2.1 Manajemen RAT
| ID | Fitur | Status |
|----|-------|--------|
| R1 | Buat RAT baru + auto-generate 3 sesi | ✅ |
| R2 | Pilih RAT aktif (session-based) | ✅ |
| R3 | Riwayat RAT | ✅ |
| R4 | Selesaikan/hapus RAT | ✅ |

### 2.2 Manajemen Sesi
| ID | Fitur | Status |
|----|-------|--------|
| S1 | CRUD sesi per RAT | ✅ |
| S2 | Kunci/buka kunci sesi | ✅ |
| S3 | Sesi terkunci = absensi ditolak | ✅ |

### 2.3 Data Anggota (Data Induk)
| ID | Fitur | Status |
|----|-------|--------|
| A1 | CRUD anggota (NIP, Nama, Dept, Section, Shift) | ✅ |
| A2 | Import dari Excel | ✅ |
| A3 | Export ke Excel | ✅ |
| A4 | Filter: search, dept, section, shift, RFID, status | ✅ |
| A5 | Pendaftaran RFID massal | ✅ |
| A6 | Nonaktifkan/aktifkan anggota | ✅ |
| A7 | Hapus semua data (dengan konfirmasi teks) | ✅ |
| A8 | Kolom No dengan penomoran per halaman | ✅ |

### 2.4 Peserta RAT
| ID | Fitur | Status |
|----|-------|--------|
| P1 | Terpisah dari data induk | ✅ |
| P2 | Import dari Excel (Google Form) | ✅ |
| P3 | Tambah manual dengan auto-fill dari data induk | ✅ |
| P4 | Filter: search, dept, section, shift, RFID | ✅ |
| P5 | Pendaftaran RFID massal (tap-tap auto-next) | ✅ |
| P6 | Export ke Excel | ✅ |
| P7 | 3 stat cards (total, kuorum, RFID) | ✅ |
| P8 | Pagination preserve GET params | ✅ |
| P9 | Auto-sync dept/section dari data induk setelah import | ✅ |

### 2.5 Absensi
| ID | Fitur | Status |
|----|-------|--------|
| AB1 | Input RFID (tap kartu) | ✅ |
| AB2 | Input NIP manual (6 digit) | ✅ |
| AB3 | Auto-detect metode (RFID ≥8 char, manual 6 digit) | ✅ |
| AB4 | Verifikasi foto kamera (toggle ON/OFF) | ✅ |
| AB5 | Auto-capture foto saat submit | ✅ |
| AB6 | Notifikasi glass premium (foto 100px + glow + pills) | ✅ |
| AB7 | 4 jenis notifikasi (sukses/duplikat/ditolak/RFID unknown) | ✅ |
| AB8 | Batalkan absensi (10 detik countdown + modal) | ✅ |
| AB9 | Tabel 5 terakhir (7 kolom, auto-refresh) | ✅ |
| AB10 | Audio beep (sukses/error) | ✅ |
| AB11 | Offline fallback (localStorage queue + auto-sync) | ✅ |
| AB12 | Indikator koneksi server | ✅ |
| AB13 | Foto tersimpan ke server + database | ✅ |
| AB14 | NIP tidak terdaftar → notif merah (tolak) | ✅ |

### 2.6 Dashboard
| ID | Fitur | Status |
|----|-------|--------|
| D1 | 3 stat cards gradient (hadir, kuorum, update) | ✅ |
| D2 | Card per sesi (progress bar + persentase) | ✅ |
| D3 | Polling real-time 5 detik | ✅ |
| D4 | Status kuorum real-time | ✅ |
| D5 | Hover bounce animation (card-hover class) | ✅ |
| D6 | Quick action buttons | ✅ |

### 2.7 Doorprize
| ID | Fitur | Status |
|----|-------|--------|
| DP1 | CRUD hadiah (nama, jumlah, kategori, gambar) | ✅ |
| DP2 | Edit hadiah via modal | ✅ |
| DP3 | Tabel eligible (hadir di semua sesi) | ✅ |
| DP4 | Filter eligible: search, dept, section, shift | ✅ |
| DP5 | Pagination eligible (client-side, 20/halaman) | ✅ |
| DP6 | Random picker (slot machine animation) | ✅ |
| DP7 | Sound effect tada (Web Audio API) | ✅ |
| DP8 | Confetti 120 partikel saat pemenang | ✅ |
| DP9 | Fullscreen mode | ✅ |
| DP10 | Riwayat pemenang (dept + section live JOIN) | ✅ |
| DP11 | Export pemenang ke Excel | ✅ |

### 2.8 Rekapitulasi
| ID | Fitur | Status |
|----|-------|--------|
| RK1 | Rekap absensi per sesi | ✅ |
| RK2 | Export Excel per sesi | ✅ |
| RK3 | Export foto per sesi dalam ZIP | ✅ |
| RK4 | Cetak daftar hadir | ✅ |

### 2.9 Sistem
| ID | Fitur | Status |
|----|-------|--------|
| SY1 | Login dengan role admin/operator | ✅ |
| SY2 | CSRF protection | ✅ |
| SY3 | Log aktivitas | ✅ |
| SY4 | Responsive mobile (640px) | ✅ |
| SY5 | Loading skeleton CSS | ✅ |

---

## 3. Arsitektur

### 3.1 Tech Stack
- **Backend:** CodeIgniter 4.7.3, PHP 8.x
- **Database:** MySQL 8.x
- **Frontend:** Custom CSS (Tailwind-inspired), Vanilla JavaScript
- **Export:** PhpSpreadsheet, ZipArchive
- **Audio:** Web Audio API
- **Camera:** MediaDevices API

### 3.2 Database Schema

```
tb_rat (id, nama_rat, tahun_buku, status, created_at)
    ↓ 1:N
tb_sesi (id, id_rat, nama_sesi, waktu_mulai, waktu_selesai, status, dikunci_pada)
    ↓ 1:N
tb_absensi (id, id_sesi, id_anggota, nama_snapshot, nip_snapshot, waktu_absen, metode, foto_path)

tb_anggota (id, nip, nama, departemen, section, shift, uid_rfid, status)

tb_peserta_rat (id, id_rat, nip, nama, departemen, section, shift, uid_rfid, created_at)

tb_doorprize_items (id, id_rat, nama_barang, jumlah, kategori, gambar_path)
    ↓ 1:N
tb_doorprize_winners (id, id_rat, id_item, nip, nama, departemen, section, waktu_undi)

tb_users (id, username, password, role, created_at)
tb_log_aktivitas (id, id_user, aksi, waktu)
```

### 3.3 Alur RFID
```
Tap kartu → uid_rfid lookup di tb_peserta_rat
    → found → resolve NIP → cek duplikasi → simpan absensi + foto
    → not found → lookup di tb_anggota
        → found → resolve NIP → cek di peserta RAT → proses
        → not found → "Kartu Belum Terdaftar"
```

### 3.4 Alur Foto
```
Kamera ON → snap() → base64 JPEG 0.7
    → POST ke /absensi/proses (FormData)
    → simpanFoto() → FCPATH/uploads/absensi-foto/YYYY-MM-DD/NIP_sesiXX_HHMMSS.jpg
    → foto_path disimpan ke tb_absensi (allowedFields harus include foto_path)
    → response include foto_path → notifikasi tampilkan foto
```

---

## 4. Keputusan Desain

| Keputusan | Opsi Dipilih | Alasan |
|-----------|-------------|--------|
| Camera layout | Opsi C — compact di dalam card input | Tidak memakan ruang, toggle ON/OFF |
| Notifikasi | Varian 3 — glass + glow + pills 100px | Premium, informatif, foto besar |
| NIP tidak terdaftar | Tolak saja (notif merah) | Simpel, operator tambah manual di Peserta RAT |
| Batalkan timeout | 10 detik | Cukup waktu tanpa terlalu lama |
| Sound doorprize | Tada saja (tanpa drum roll) | Simpel, tidak berlebihan |
| Pagination | kpni_pager template + preserve GET | Konsisten, filter tidak hilang |
| Hover animation | CSS class `card-hover` | Konsisten dengan tombol, smooth |
| Eligible filter | Client-side JS | Data sudah loaded, realtime |

---

## 5. Batasan & Keputusan "Tidak"

Fitur berikut **sengaja tidak dibuat** berdasarkan keputusan user:
- ❌ Session timeout warning
- ❌ Dark mode
- ❌ QR code attendance
- ❌ Chart.js dashboard
- ❌ Statistik per departemen
- ❌ Doorprize drum roll sound
- ❌ Doorprize countdown 3-2-1
- ❌ Tombol "Tambah & Absen" di notifikasi absensi

---

## 6. Catatan Teknis Penting

### Bug yang Pernah Ditemukan & Diperbaiki
1. `AbsensiModel::$allowedFields` harus include `foto_path` — tanpa ini foto tersimpan ke disk tapi NULL di database
2. CI4 Model query builder stateful setelah `countAllResults()` — gunakan `db_connect()->query()` untuk query berikutnya
3. `getLimaTerakhir()` tidak melihat record baru — patch `lima[0].foto_path` setelah insert
4. `paginate(20, 'peserta')` membuat CI4 cari param `page_peserta` — gunakan `paginate(20)` tanpa group untuk param `page` default
5. `animation-fill-mode: both` pada `.page-enter .card` memblock semua `:hover { transform }` — hapus `both`
6. `backdrop-filter:blur()` pada `.card` menyebabkan animasi transform choppy — tambah `backdrop-filter:none` inline pada card yang perlu hover smooth
7. Doorprize winner section selalu "-" — gunakan live JOIN query dengan COALESCE

### Convention
- Session key RAT aktif: `rat_dipilih` (bukan `rat_aktif_id`)
- `catatLog()` adalah private method di setiap Controller (bukan BaseController)
- Auth check: `session('role') !== 'admin'`
- Pager template: `app/Views/_partials/kpni_pager.php`
- Modal harus di-`appendChild` ke `document.body` (parent `backdrop-filter` break `position:fixed`)

---

© 2026 Ridzkal Jamil — Universitas Pelita Bangsa
