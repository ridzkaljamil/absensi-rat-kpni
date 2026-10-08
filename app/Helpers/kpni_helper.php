<?php

/**
 * Helper umum Sistem Absensi RAT KPNI.
 * Di-load otomatis lewat BaseController::$helpers.
 */

if (! function_exists('hitung_kuorum')) {
    /**
     * Kuorum = lebih dari setengah peserta (50% + 1).
     * Contoh: 560 -> 281, 240 -> 121, 5 -> 3. Peserta 0 -> 0.
     */
    function hitung_kuorum(int $totalPeserta): int
    {
        return $totalPeserta > 0 ? intdiv($totalPeserta, 2) + 1 : 0;
    }
}

if (! function_exists('label_sesi')) {
    /**
     * Nama sesi untuk ditampilkan. Kata "Sesi" hanya ditambahkan kalau
     * namanya belum diawali "Sesi" — supaya "Pagi" jadi "Sesi Pagi",
     * tapi "Sesi 1" tidak jadi "Sesi Sesi 1".
     */
    function label_sesi(?string $namaSesi): string
    {
        $nama = trim((string) $namaSesi);
        if ($nama === '') {
            return 'Sesi';
        }

        return stripos($nama, 'sesi') === 0 ? $nama : 'Sesi ' . $nama;
    }
}

if (! function_exists('label_rat')) {
    /**
     * Label RAT untuk navbar: tahun buku hanya ditambahkan kalau belum
     * tertulis di nama RAT (hindari "… Tahun Buku 2027 (2027)").
     */
    function label_rat(array $rat): string
    {
        $nama  = trim((string) ($rat['nama_rat'] ?? ''));
        $tahun = (string) ($rat['tahun_buku'] ?? '');

        return ($tahun === '' || str_contains($nama, $tahun)) ? $nama : "{$nama} ({$tahun})";
    }
}

if (! function_exists('jam_valid')) {
    /**
     * Validasi format jam 24 jam "HH:MM" (00:00 - 23:59).
     * Mengembalikan "HH:MM:00" kalau valid, null kalau tidak.
     */
    function jam_valid(?string $jam): ?string
    {
        $jam = trim((string) $jam);
        if (preg_match('/^([01]?\d|2[0-3])[:.]([0-5]\d)$/', $jam, $m)) {
            return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
        }

        return null;
    }
}
