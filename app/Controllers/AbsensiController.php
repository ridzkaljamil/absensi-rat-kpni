<?php

namespace App\Controllers;

use App\Models\AbsensiModel;
use App\Models\AnggotaModel;
use App\Models\PesertaRatModel;
use App\Models\RatModel;
use App\Models\SesiModel;

class AbsensiController extends BaseController
{
    protected AbsensiModel $absensiModel;
    protected SesiModel $sesiModel;
    protected RatModel $ratModel;
    protected AnggotaModel $anggotaModel;
    protected PesertaRatModel $pesertaModel;

    public function __construct()
    {
        $this->absensiModel = new AbsensiModel();
        $this->sesiModel    = new SesiModel();
        $this->ratModel     = new RatModel();
        $this->anggotaModel = new AnggotaModel();
        $this->pesertaModel = new PesertaRatModel();
    }

    public function index()
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return redirect()->to('/rat')->with('error', 'Pilih atau buat RAT terlebih dahulu.');
        }

        $daftarSesi = $this->sesiModel->getByRat($idRat);
        if (empty($daftarSesi)) {
            return redirect()->to('/sesi')->with('error', 'Belum ada sesi. Buat sesi terlebih dahulu.');
        }

        // Default: sesi aktif pertama, kalau semua terkunci pakai sesi pertama
        $sesi = $daftarSesi[0];
        foreach ($daftarSesi as $s) {
            if ($s['status'] === 'aktif') {
                $sesi = $s;
                break;
            }
        }

        return $this->tampilHalamanAbsensi($this->ratModel->find($idRat), $sesi, $daftarSesi);
    }

    public function pilihSesi($idSesi)
    {
        $idRat = $this->idRatDipilih();
        if (! $idRat) {
            return redirect()->to('/rat');
        }

        $sesi = $this->sesiModel->find($idSesi);
        if (! $sesi || (int) $sesi['id_rat'] !== $idRat) {
            return redirect()->to('/absensi');
        }

        return $this->tampilHalamanAbsensi($this->ratModel->find($idRat), $sesi, $this->sesiModel->getByRat($idRat));
    }

    private function tampilHalamanAbsensi(array $rat, array $sesi, array $daftarSesi)
    {
        return view('absensi/index', [
            'rat'          => $rat,
            'sesi'         => $sesi,
            'daftarSesi'   => $daftarSesi,
            'jumlahHadir'  => $this->absensiModel->countBySesi((int) $sesi['id']),
            'totalAnggota' => $this->pesertaModel->totalPesertaSesi((int) $sesi['id']),
            'terbaruAbsen' => $this->getLimaTerakhir((int) $sesi['id']),
        ]);
    }

    public function proses()
    {
        if (! session()->has('user_id')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Sesi habis, silakan login ulang.']);
        }

        $idSesi     = (int) $this->request->getPost('id_sesi');
        $nilaiInput = trim((string) $this->request->getPost('nip'));
        $metode     = $this->request->getPost('metode') === 'rfid' ? 'rfid' : 'manual';
        $fotoBase64 = (string) $this->request->getPost('foto');

        if ($nilaiInput === '' || ! $idSesi) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $sesi = $this->cekSesiTerbuka($idSesi);
        if (is_string($sesi)) {
            return $this->response->setJSON(['status' => 'error', 'message' => $sesi]);
        }
        $idRat = (int) $sesi['id_rat'];

        // ═══ RESOLUSI INPUT → NIP ═══
        if ($metode === 'rfid') {
            // Kartu cukup didaftarkan sekali: cari di peserta sesi mana pun di RAT ini,
            // lalu fallback ke data induk anggota.
            $pemilik = $this->pesertaModel->cariByRfidDiRat($idRat, $nilaiInput)
                ?? $this->anggotaModel->findByUidRfid($nilaiInput);
            if (! $pemilik) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Kartu RFID ini belum terdaftar di sistem.',
                    'kode'    => 'kartu_belum_terdaftar',
                ]);
            }
            $nip = $pemilik['nip'];
        } else {
            $nip = $nilaiInput;
        }

        // Peserta harus terdaftar di SESI ini (kuorum dihitung per sesi)
        $peserta = $this->pesertaModel->nipTerdaftarSesi($idSesi, $nip);
        if (! $peserta) {
            return $this->response->setJSON([
                'status'       => 'error',
                'message'      => "NIP {$nip} tidak terdaftar sebagai peserta " . label_sesi($sesi['nama_sesi']) . '.',
                'kode'         => 'bukan_peserta',
                'nip_input'    => $nip,
                'metode_input' => $metode,
            ]);
        }

        // Cek data induk
        $anggota = $this->anggotaModel->findByPrNumber($nip);
        if (! $anggota) {
            return $this->response->setJSON([
                'status'  => 'konfirmasi_induk',
                'message' => "NIP {$nip} ({$peserta['nama']}) tidak ada di data induk.",
                'peserta' => $peserta,
                'id_sesi' => $idSesi,
                'metode'  => $metode,
            ]);
        }

        if ($anggota['status'] === 'nonaktif') {
            return $this->response->setJSON(['status' => 'error', 'message' => "{$anggota['nama']} berstatus nonaktif."]);
        }

        $idAnggota = (int) $anggota['id'];

        // ═══ CEK DUPLIKASI — kembalikan detail absensi sebelumnya ═══
        if ($this->absensiModel->sudahAbsenDiSesi($idSesi, $idAnggota)) {
            $lama = db_connect()->query(
                'SELECT waktu_absen, metode, foto_path FROM tb_absensi WHERE id_sesi = ? AND id_anggota = ? LIMIT 1',
                [$idSesi, $idAnggota]
            )->getRowArray();

            return $this->response->setJSON([
                'status'  => 'error',
                'kode'    => 'sudah_absen',
                'message' => "{$anggota['nama']} sudah absen di sesi ini.",
                'detail'  => [
                    'nama'       => $anggota['nama'],
                    'nip'        => $anggota['nip'],
                    'departemen' => $anggota['departemen'] ?? '-',
                    'section'    => $anggota['section'] ?? '-',
                    'shift'      => $anggota['shift'] ?? '-',
                    'waktu'      => $lama ? substr($lama['waktu_absen'], 11, 8) : '-',
                    'metode'     => $lama['metode'] ?? '-',
                    'foto_path'  => ! empty($lama['foto_path']) ? $lama['foto_path'] : null,
                ],
            ]);
        }

        $hasil = $this->simpanAbsensi($idSesi, $idAnggota, $anggota['nama'], $anggota['nip'], $metode, $fotoBase64);
        $this->catatLog("Absensi: {$anggota['nama']} (NIP {$nip}) " . label_sesi($sesi['nama_sesi']) . " via {$metode}");

        return $this->response->setJSON([
            'status'     => 'success',
            'id_absensi' => $hasil['id'],
            'nama'       => $anggota['nama'],
            'nip'        => $anggota['nip'],
            'departemen' => $anggota['departemen'] ?? '-',
            'section'    => $anggota['section'] ?? '-',
            'shift'      => $anggota['shift'] ?? '-',
            'waktu'      => date('H:i:s'),
            'metode'     => $metode,
            'foto_path'  => $hasil['foto_path'],
            'lima'       => $this->getLimaTerakhir($idSesi),
        ]);
    }

    /**
     * Peserta terdaftar di sesi, tapi belum ada di data induk:
     * operator konfirmasi → otomatis dibuatkan data induk lalu diabsenkan.
     */
    public function prosesKonfirmasiInduk()
    {
        if (! session()->has('user_id')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Sesi habis.']);
        }

        $nip    = trim((string) $this->request->getPost('nip'));
        $idSesi = (int) $this->request->getPost('id_sesi');
        $metode = $this->request->getPost('metode') === 'rfid' ? 'rfid' : 'manual';
        $foto64 = (string) $this->request->getPost('foto');

        $sesi = $this->cekSesiTerbuka($idSesi);
        if (is_string($sesi)) {
            return $this->response->setJSON(['status' => 'error', 'message' => $sesi]);
        }

        $peserta = $this->pesertaModel->nipTerdaftarSesi($idSesi, $nip);
        if (! $peserta) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Peserta tidak ditemukan.']);
        }

        $anggota = $this->anggotaModel->findByPrNumber($nip);
        if ($anggota) {
            $idAnggota = (int) $anggota['id'];
        } else {
            $rfid = $peserta['uid_rfid'] ?: null;
            if ($rfid && $this->anggotaModel->uidRfidSudahAda($rfid)) {
                $rfid = null; // UID dipakai anggota lain — jangan bentrok unique key
            }
            $idAnggota = (int) $this->anggotaModel->insert([
                'nip'        => $peserta['nip'],
                'nama'       => $peserta['nama'],
                'departemen' => $peserta['departemen'],
                'section'    => $peserta['section'],
                'shift'      => $peserta['shift'],
                'uid_rfid'   => $rfid,
                'status'     => 'aktif',
            ]);
            if (! $idAnggota) {
                $err = implode(' ', $this->anggotaModel->errors()) ?: 'Gagal menambah data induk.';
                return $this->response->setJSON(['status' => 'error', 'message' => $err]);
            }
            $this->catatLog("Auto-tambah data induk: NIP {$nip} - {$peserta['nama']}");
        }

        if ($this->absensiModel->sudahAbsenDiSesi($idSesi, $idAnggota)) {
            return $this->response->setJSON(['status' => 'error', 'message' => "{$peserta['nama']} sudah absen.", 'kode' => 'sudah_absen']);
        }

        $hasil = $this->simpanAbsensi($idSesi, $idAnggota, $peserta['nama'], $peserta['nip'], $metode, $foto64);
        $this->catatLog("Absensi (tambah induk): NIP {$nip} " . label_sesi($sesi['nama_sesi']));

        return $this->response->setJSON([
            'status'     => 'success',
            'id_absensi' => $hasil['id'],
            'nama'       => $peserta['nama'],
            'nip'        => $peserta['nip'],
            'waktu'      => date('H:i:s'),
            'metode'     => $metode,
            'foto_path'  => $hasil['foto_path'],
            'lima'       => $this->getLimaTerakhir($idSesi),
        ]);
    }

    /**
     * Batalkan absensi (maks. 10 detik setelah dicatat) — dipakai saat
     * operator melihat foto tidak cocok di notifikasi.
     */
    public function batalkan()
    {
        $absensi = $this->absensiModel->find((int) $this->request->getPost('id_absensi'));
        if (! $absensi) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }

        if (time() - strtotime($absensi['waktu_absen']) > 10) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Sudah lebih dari 10 detik, tidak bisa dibatalkan.']);
        }

        if ($absensi['foto_path'] && is_file(FCPATH . $absensi['foto_path'])) {
            unlink(FCPATH . $absensi['foto_path']);
        }

        $this->absensiModel->delete($absensi['id']);
        $this->catatLog("Batalkan absensi: {$absensi['nama_snapshot']} (NIP {$absensi['nip_snapshot']}) sesi {$absensi['id_sesi']}");

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Absensi {$absensi['nama_snapshot']} dibatalkan.",
        ]);
    }

    /**
     * API: 5 absensi terakhir untuk refresh tabel via AJAX.
     */
    public function limaTerakhir()
    {
        $idSesi = (int) $this->request->getGet('id_sesi');

        return $this->response->setJSON(['lima' => $idSesi ? $this->getLimaTerakhir($idSesi) : []]);
    }

    // ─────────────────────────────────────────────
    //  Helper
    // ─────────────────────────────────────────────

    /**
     * Pastikan sesi ada, milik RAT yang dipilih, dan tidak terkunci.
     * Return array sesi, atau string pesan error.
     */
    private function cekSesiTerbuka(int $idSesi): array|string
    {
        $sesi = $idSesi ? $this->sesiModel->find($idSesi) : null;
        if (! $sesi || (int) $sesi['id_rat'] !== $this->idRatDipilih()) {
            return 'Sesi tidak valid. Muat ulang halaman absensi.';
        }
        if ($sesi['status'] === 'terkunci') {
            return 'Sesi sudah terkunci.';
        }

        return $sesi;
    }

    /**
     * @return array{id:int, foto_path:?string}
     */
    private function simpanAbsensi(int $idSesi, int $idAnggota, string $nama, string $nip, string $metode, string $foto64): array
    {
        $fotoPath = str_starts_with($foto64, 'data:image') ? $this->simpanFoto($foto64, $nip, $idSesi) : null;

        $this->absensiModel->insert([
            'id_sesi'       => $idSesi,
            'id_anggota'    => $idAnggota,
            'nama_snapshot' => $nama,
            'nip_snapshot'  => $nip,
            'waktu_absen'   => date('Y-m-d H:i:s'),
            'metode'        => $metode,
            'foto_path'     => $fotoPath,
        ]);

        return ['id' => (int) $this->absensiModel->getInsertID(), 'foto_path' => $fotoPath];
    }

    private function simpanFoto(string $base64, string $nip, int $idSesi): ?string
    {
        $data = explode(',', $base64, 2);
        if (count($data) !== 2) {
            return null;
        }

        $decoded = base64_decode($data[1], true);
        // Pastikan isinya benar-benar gambar sebelum disimpan di folder publik
        if ($decoded === false || @getimagesizefromstring($decoded) === false) {
            return null;
        }

        $tanggal = date('Y-m-d');
        $dir     = FCPATH . 'uploads/absensi-foto/' . $tanggal . '/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = preg_replace('/[^0-9A-Za-z]/', '', $nip) . '_sesi' . $idSesi . '_' . date('His') . '.jpg';
        if (file_put_contents($dir . $filename, $decoded) === false) {
            return null;
        }

        return 'uploads/absensi-foto/' . $tanggal . '/' . $filename;
    }

    private function getLimaTerakhir(int $idSesi): array
    {
        $rows = db_connect()->query('
            SELECT ab.nama_snapshot, ab.nip_snapshot, ab.waktu_absen, ab.metode, ab.foto_path,
                   COALESCE(NULLIF(p.departemen, ""), NULLIF(a.departemen, ""), "-") AS departemen,
                   COALESCE(NULLIF(p.section, ""),    NULLIF(a.section, ""),    "-") AS section,
                   COALESCE(NULLIF(p.shift, ""),      NULLIF(a.shift, ""),      "-") AS shift
            FROM tb_absensi ab
            LEFT JOIN tb_anggota a     ON a.id = ab.id_anggota
            LEFT JOIN tb_peserta_rat p ON p.nip = ab.nip_snapshot AND p.id_sesi = ab.id_sesi
            WHERE ab.id_sesi = ?
            ORDER BY ab.waktu_absen DESC, ab.id DESC
            LIMIT 5
        ', [$idSesi])->getResultArray();

        return array_map(static fn ($r) => [
            'nama'       => $r['nama_snapshot'],
            'nip'        => $r['nip_snapshot'],
            'departemen' => $r['departemen'],
            'section'    => $r['section'],
            'shift'      => $r['shift'],
            'waktu'      => substr($r['waktu_absen'], 11, 8),
            'metode'     => $r['metode'],
            'foto_path'  => $r['foto_path'] ?? null,
        ], $rows);
    }
}
