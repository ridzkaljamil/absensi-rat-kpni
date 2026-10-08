# Design System — Absensi Digital RAT KPNI

> Dokumen referensi lengkap untuk design system, komponen UI, dan panduan visual.

---

## 1. Identitas Visual

### Warna Utama (Brand KPNI)
| Token | Hex | Penggunaan |
|-------|-----|------------|
| Navy Dark | `#022760` | Background gradient kiri, teks heading |
| Navy | `#102A83` | Background gradient tengah |
| Navy Medium | `#064687` | Stat card 2, hover state |
| Blue Primary | `#0960A8` | Accent utama, border aktif, link |
| Sky | `#009EE0` | Highlight, progress bar, badge aktif |
| Light Blue | `#E8F4FD` | Background info card, modal icon bg |

### Warna Aksen & Status
| Token | Hex | Penggunaan |
|-------|-----|------------|
| Green Glow | `#7FFFB4` | Notif sukses glow, kuorum tercapai |
| Green | `#16a34a` | Badge sukses, checkmark |
| Amber | `#C9920F` | Notif duplikat, badge warning |
| Amber Glow | `#FFD97D` | Notif duplikat glow |
| Rose | `#A8295A` | Notif error, tombol hapus |
| Rose Light | `#F09595` | Notif ditolak border |

### Tipografi
- Font: System stack `-apple-system, 'Segoe UI', sans-serif`
- Heading: 800 (Extra Bold)
- Body: 400-500
- Monospace (NIP): `'SF Mono', Consolas, monospace`

---

## 2. Layout System

### Spacing
- Page padding: `24px 20px`
- Card padding: `20px` (p-5)
- Gap antar card: `16px`
- Grid: `repeat(3, 1fr)` untuk stat cards dan sesi cards

### Container
- Max width: `960px` (absensi), `1152px` (tabel)
- Card border-radius: `16px`
- Modal border-radius: `20px`

---

## 3. Komponen

### 3.1 Stat Cards (Dashboard, Peserta RAT)
```html
<div class="card card-hover p-5" style="background:linear-gradient(...);
     border:none;cursor:pointer;backdrop-filter:none;">
```
- Background: gradient navy 135deg
- Hover: `card-hover` class → `translateY(-4px)` + box-shadow
- `backdrop-filter:none` wajib untuk smooth hover animation
- Label: 12px uppercase, letter-spacing 0.05em, color rgba(255,255,255,0.7)
- Value: 40px font-weight 800, color white
- Sublabel: 12px, color #009EE0

### 3.2 Sesi Cards
```html
<div class="card card-hover p-5" style="cursor:pointer;backdrop-filter:none;">
```
- Background: white card default
- Hover: `card-hover` class (sama dengan stat card)
- Progress bar: `.progress-track` (grey) + `.progress-fill` (gradient blue)
- Badge status: `badge-active` (teal) / `badge-locked` (grey)

### 3.3 Filter Card
```html
<form method="GET" class="card p-4 mb-4">
    <div style="display:flex;gap:6px;align-items:center;">
        <input style="flex:2;min-width:0;height:36px;">
        <select style="flex:1.5;min-width:0;height:36px;">
        ...
        <button class="btn btn-primary" style="flex-shrink:0;">
    </div>
</form>
```
- Semua input/select pakai `flex` ratio → mengisi penuh lebar card
- `min-width:0` mencegah overflow
- Tombol filter/reset pakai `flex-shrink:0`
- Height seragam: 36px

### 3.4 Notifikasi Absensi (Varian 3 — Glass + Glow)

#### Sukses
```
┌─ border-left: 5px solid #7FFFB4 ────────────────────────┐
│ bg: rgba(10,94,63,0.95) backdrop-filter:blur(12px)        │
│ ┌──────────┐                                              │
│ │  FOTO    │  NAMA PESERTA (28px bold)                    │
│ │  100px   │  [NIP] [DEPT] [SECTION] [Shift X]           │
│ │  glow    │  08:23:15 WIB · RFID    [BERHASIL]          │
│ │  ring    │                          [✗ Batalkan 10s]    │
│ └──────────┘                                              │
└───────────────────────────────────────────────────────────┘
```
- Foto: 100px, border-radius 20px, glow `box-shadow: 0 0 24px rgba(127,255,180,0.15)`
- Badge ✓ (28px circle, #16a34a) di kanan bawah foto
- Pills: NIP (monospace, green bg), Dept/Section (white bg 0.08), Shift (green bg)
- Label "BERHASIL": green pill `rgba(127,255,180,0.2)` color `#7FFFB4`

#### Duplikat (Sudah Absen)
- Background: `rgba(146,101,10,0.95)`
- Border-left: `#FFD97D`
- Foto glow: amber `rgba(255,217,125,...)`
- Badge ⚠ (amber)
- Label: "SUDAH ABSEN"
- Menampilkan foto dari absensi pertama

#### Tidak Terdaftar
- Background: `rgba(107,31,58,0.95)`
- Border-left: `#F09595`
- Glow circle decoration

#### RFID Belum Terdaftar
- Background: `rgba(9,96,168,0.95)`
- Border-left: `#009EE0`

### 3.5 Tombol (Buttons)
```css
.btn { transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1); }
.btn:hover { transform:translateY(-2px); }
.btn:active { transform:scale(0.97);transition-duration:0.1s; }
```
Varian: `btn-primary` (blue), `btn-edit` (navy), `btn-danger` (rose), `btn-teal`, `btn-amber`, `btn-ghost`, `btn-indigo`

### 3.6 Pagination (kpni_pager.php)
- Tombol: `min-width:32px; height:32px; border-radius:6px`
- Active: `background:#0960A8; color:white`
- Hover: bounce `translateY(-2px) scale(1.08)` + bg `#E8F4FD`
- Disabled: grey, `cursor:not-allowed`
- Navigation: « First, ‹ Prev, [1][2][3], Next ›, » Last
- SurroundCount: 2
- Preserve GET params (search, filter) via `http_build_query`

### 3.7 Tabel Data
```css
.data-table { width:100%; border-collapse:collapse; }
.data-table th { background:rgba(9,96,168,0.06); font-size:11px; text-transform:uppercase; }
.data-table td { padding:10px 14px; border-bottom:1px solid #f3f4f6; }
```
- Kolom No: `color:#9ca3af; font-size:12px`
- Kolom NIP: `font-family:monospace; font-weight:600; color:#0960A8`
- Kolom Shift: badge `badge-info`
- Penomoran per halaman: `(currentPage - 1) * perPage + index + 1`

### 3.8 Modal
```html
<div class="modal-overlay">
    <div class="modal-box">...</div>
</div>
```
- Overlay: `rgba(2,39,96,0.45)` + `backdrop-filter:blur(8px)`
- Box: `border-radius:20px; padding:28px`
- Icon header: 44px circle with colored bg
- `document.body.appendChild()` untuk hindari parent `backdrop-filter` block `position:fixed`
- Message support HTML via `innerHTML`

### 3.9 Card Absensi Header
```
┌─ gradient 135deg #022760 → #102A83 → #0960A8 ──────────┐
│ ○ glow circle (top-right)  ○ glow (bottom-left)         │
│ RAT 2025 · TB 2024                    [← Dashboard]     │
│ SESI PAGI   08:00 – 12:00 WIB                           │
│ [👥] 45 / 560 hadir   [● Aktif]                         │
└──────────────────────────────────────────────────────────┘
```
- Glow circles: 2 buah, `rgba(0,158,224,0.08)` dan `rgba(127,255,180,0.05)`
- Icon box: 30px, `rgba(0,158,224,0.15)`, Tabler icon
- Counter: 28px bold

---

## 4. Animasi & Transisi

### Hover Bounce (Tombol)
```css
transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
transform: translateY(-2px);  /* hover */
transform: scale(0.97);       /* active */
```

### Hover Bounce (Card)
```css
/* Menggunakan class card-hover dari main.php */
.card-hover:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(9,96,168,0.14); }
```
**PENTING:** Card dengan gradient background harus punya `backdrop-filter:none` inline untuk smooth animation. `animation-fill-mode: both` pada page entry animation harus dihapus agar hover transform tidak di-block.

### Page Entry
```css
@keyframes pageEnter { 0%{opacity:0;transform:translateY(12px)} 100%{opacity:1;transform:translateY(0)} }
.page-enter .card { animation: pageEnter 0.3s cubic-bezier(0.34,1.56,0.64,1); }
/* TIDAK BOLEH pakai 'both' — akan block hover transform */
```

### Notifikasi
```css
@keyframes slideUp { 0%{opacity:0;transform:translateY(8px)} 100%{opacity:1;transform:translateY(0)} }
```

### Pulse (Live indicator)
```css
@keyframes pulse { 0%,100%{opacity:.5} 50%{opacity:1} }
```

### Confetti (Doorprize)
- 120 partikel, 7 warna: `#7FFFB4, #FFD97D, #009EE0, #F09595, #AFA9EC, #5DCAA5, #F0997B`
- Canvas overlay `position:fixed; pointer-events:none`
- Physics: velocity, gravity, rotation

---

## 5. Sound Effects

### Absensi Beep
- Sukses: 880Hz → 1100Hz sine wave, 0.3s
- Error: 300Hz sine wave, 0.5s

### Doorprize Tada
- 4 nada ascending: C5(523) → E5(659) → G5(784) → C6(1047)
- Interval 120ms per nada
- Sine wave, 0.3 gain, exponential decay

---

## 6. Responsive

### Breakpoint: 640px
```css
@media (max-width: 640px) {
    .data-table { font-size:12px; }
    .data-table th, td { padding:8px 10px; }
    .btn { font-size:12px; height:32px; }
    .card { border-radius:12px; }
}
```

### Absensi Camera
```css
@media (max-width:640px) {
    #col-kamera { flex: 0 0 120px !important; }
}
```

---

## 7. Foto Storage

### Path Convention
```
public/uploads/absensi-foto/YYYY-MM-DD/NIP_sesiXX_HHMMSS.jpg
```
- JPEG quality: 0.7 (dari canvas.toDataURL)
- Disimpan ke DB di kolom `foto_path` (AbsensiModel allowedFields)
- Serve via web server langsung (`/uploads/...`)
- Export ZIP: file dinamai `NIP_Nama.jpg`

---

© 2026 Ridzkal Jamil — Universitas Pelita Bangsa
