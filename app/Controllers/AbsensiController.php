<?php

namespace App\Controllers;

use App\Libraries\KartuRfid;
use App\Models\AbsensiModel;
use App\Models\AnggotaModel;
use App\Models\PesertaRatModel;
use App\Models\RatModel;
use App\Models\SesiModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class AbsensiController extends BaseController
{
    /** Batas waktu pembatalan di layar (detik); server memberi kelonggaran 3 detik untuk jeda jaringan. */
    private const BATAS_BATAL = 10;

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
            return session()->get('role') === 'admin'
                ? redirect()->to('/rat')->with('error', 'Pilih atau buat RAT terlebih dahulu.')
                : redirect()->to('/dashboard')->with('error', 'Belum ada RAT aktif. Hubungi Admin.');
        }

        $daftarSesi = $this->sesiModel->getByRat($idRat);
        if ($daftarSesi === []) {
            return session()->get('role') === 'admin'
                ? redirect()->to('/sesi')->with('error', 'Belum ada sesi. Buat sesi terlebih dahulu.')
                : redirect()->to('/dashboard')->with('error', 'RAT ini belum punya sesi. Hubungi Admin.');
        }

        return $this->tampilHalamanAbsensi($this->ratModel->find($idRat), $this->sesiSaatIni($daftarSesi), $daftarSesi);
    }

    public function pilihSesi($idSesi)
    {
        $sesi = $this->sesiMilikRat((int) $idSesi);
        if (! $sesi) {
            return redirect()->to('/absensi');
        }

        return $this->tampilHalamanAbsensi($this->ratModel->find((int) $sesi['id_rat']), $sesi, $this->sesiModel->getByRat((int) $sesi['id_rat']));
    }

    private function tampilHalamanAbsensi(array $rat, array $sesi, array $daftarSesi)
    {
        $status = $this->cekSesiTerbuka((int) $sesi['id']);

        return view('absensi/index', [
            'terbuka'      => is_array($status),
            'pesanTutup'   => is_string($status) ? $status : '',
            'rat'          => $rat,
            'sesi'         => $sesi,
            'daftarSesi'   => $daftarSesi,
            'jumlahHadir'  => $this->absensiModel->countBySesi((int) $sesi['id']),
            'totalAnggota' => $this->pesertaModel->totalPesertaSesi((int) $sesi['id']),
            'terbaruAbsen' => $this->getLimaTerakhir((int) $sesi['id']),
        ]);
    }

    /**
     * Tap kartu / ketik NIP.
     *
     * Input diterjemahkan di server (bukan dari panjang karakter di browser):
     * NIP peserta sesi ini → kartu RFID → NIP lain. Jadi NIP yang bukan 6 digit
     * atau kartu yang kebetulan berupa angka tetap dikenali dengan benar.
     */
    public function proses()
    {
        $idSesi     = (int) $this->request->getPost('id_sesi');
        $nilaiInput = trim((string) $this->request->getPost('nip'));
        $fotoBase64 = (string) $this->request->getPost('foto');

        if ($nilaiInput === '' || ! $idSesi) {
            return $this->json('error', 'Data tidak lengkap.');
        }

        $sesi = $this->cekSesiTerbuka($idSesi);
        if (is_string($sesi)) {
            return $this->json('error', $sesi, ['kode' => 'sesi_tertutup']);
        }
        $idRat = (int) $sesi['id_rat'];

        // ═══ RESOLUSI INPUT → NIP ═══
        $nipKetik = normalisasi_nip($nilaiInput);
        $peserta  = $this->pesertaModel->nipTerdaftarSesi($idSesi, $nipKetik);
        $metode   = 'manual';

        if (! $peserta) {
            $pemilik = (new KartuRfid())->cariPemilik($nilaiInput);
            if ($pemilik) {
                $metode  = 'rfid';
                $peserta = $this->pesertaModel->nipTerdaftarSesi($idSesi, $pemilik['nip']);
                if (! $peserta) {
                    return $this->tolakBukanPeserta($sesi, $pemilik['nip'], $pemilik['nama'] ?? null);
                }
            } elseif (ctype_digit($nipKetik) && strlen($nipKetik) <= 6) {
                $induk = $this->anggotaModel->findByPrNumber($nipKetik);

                return $this->tolakBukanPeserta($sesi, $nipKetik, $induk['nama'] ?? null);
            } else {
                return $this->json('error', 'Kartu RFID ini belum terdaftar. Daftarkan dulu di menu Peserta RAT.', ['kode' => 'kartu_belum_terdaftar']);
            }
        }

        $nip     = $peserta['nip'];
        $anggota = $this->anggotaModel->findByPrNumber($nip);
        if (! $anggota) {
            return $this->json('konfirmasi_induk', "NIP {$nip} ({$peserta['nama']}) tidak ada di data induk.", [
                'peserta' => ['nip' => $nip, 'nama' => $peserta['nama']],
                'id_sesi' => $idSesi,
                'metode'  => $metode,
            ]);
        }
        if ($anggota['status'] === 'nonaktif') {
            return $this->json('error', "{$anggota['nama']} berstatus nonaktif di data induk.", ['kode' => 'nonaktif']);
        }

        return $this->catatKehadiran($sesi, $peserta, $anggota, $metode, $fotoBase64);
    }

    /**
     * Peserta terdaftar di sesi tapi belum ada di data induk:
     * operator konfirmasi → dibuatkan data induk lalu diabsenkan.
     */
    public function prosesKonfirmasiInduk()
    {
        $nip    = normalisasi_nip((string) $this->request->getPost('nip'));
        $idSesi = (int) $this->request->getPost('id_sesi');
        $metode = $this->request->getPost('metode') === 'rfid' ? 'rfid' : 'manual';

        $sesi = $this->cekSesiTerbuka($idSesi);
        if (is_string($sesi)) {
            return $this->json('error', $sesi, ['kode' => 'sesi_tertutup']);
        }

        $peserta = $this->pesertaModel->nipTerdaftarSesi($idSesi, $nip);
        if (! $peserta) {
            return $this->json('error', 'Peserta tidak ditemukan di sesi ini.');
        }

        $anggota = $this->anggotaModel->findByPrNumber($nip);
        if (! $anggota) {
            $idBaru = $this->anggotaModel->insert([
                'nip'        => $peserta['nip'],
                'nama'       => $peserta['nama'],
                'departemen' => $peserta['departemen'],
                'section'    => $peserta['section'],
                'shift'      => $peserta['shift'],
                'status'     => 'aktif',
            ]);
            if (! $idBaru) {
                return $this->json('error', implode(' ', $this->anggotaModel->errors()) ?: 'Gagal menambah data induk.');
            }
            $this->catatLog("Auto-tambah data induk: NIP {$nip} - {$peserta['nama']}");
            $anggota = $this->anggotaModel->find($idBaru);
        }
        if ($anggota['status'] === 'nonaktif') {
            return $this->json('error', "{$anggota['nama']} berstatus nonaktif di data induk.", ['kode' => 'nonaktif']);
        }

        return $this->catatKehadiran($sesi, $peserta, $anggota, $metode, (string) $this->request->getPost('foto'));
    }

    /**
     * Batalkan absensi milik perangkat/akun ini, maksimal 10 detik setelah dicatat.
     */
    public function batalkan()
    {
        $absensi = $this->absensiModel->find((int) $this->request->getPost('id_absensi'));
        if (! $absensi) {
            return $this->json('error', 'Data absensi tidak ditemukan (mungkin sudah dibatalkan).');
        }

        $sesi = $this->cekSesiTerbuka((int) $absensi['id_sesi']);
        if (is_string($sesi)) {
            return $this->json('error', $sesi);
        }
        if (! in_array((int) $absensi['id'], session()->get('absen_saya') ?? [], true)) {
            return $this->json('error', 'Absensi ini dicatat dari perangkat lain, tidak bisa dibatalkan dari sini.');
        }
        if (time() - strtotime($absensi['waktu_absen']) > self::BATAS_BATAL + 3) {
            return $this->json('error', 'Sudah lebih dari ' . self::BATAS_BATAL . ' detik, tidak bisa dibatalkan.');
        }

        $this->absensiModel->delete($absensi['id']);
        $this->hapusFoto($absensi['foto_path']);
        $this->catatLog("Batalkan absensi: {$absensi['nama_snapshot']} (NIP {$absensi['nip_snapshot']}) " . label_sesi($sesi['nama_sesi']));

        return $this->json('success', "Absensi {$absensi['nama_snapshot']} dibatalkan.", [
            'jumlah' => $this->absensiModel->countBySesi((int) $absensi['id_sesi']),
        ]);
    }

    /**
     * API: 5 absensi terakhir + jumlah hadir, untuk menyegarkan halaman absensi.
     */
    public function limaTerakhir()
    {
        $sesi = $this->sesiMilikRat((int) $this->request->getGet('id_sesi'));
        if (! $sesi) {
            return $this->json('error', 'Sesi tidak valid.', ['lima' => []]);
        }

        $status = $this->cekSesiTerbuka((int) $sesi['id']);

        return $this->response->setJSON([
            'status'  => 'success',
            'lima'    => $this->getLimaTerakhir((int) $sesi['id']),
            'jumlah'  => $this->absensiModel->countBySesi((int) $sesi['id']),
            'total'   => $this->pesertaModel->totalPesertaSesi((int) $sesi['id']),
            'terbuka' => is_array($status),               // sesi aktif & RAT masih berjalan
            'pesan'   => is_string($status) ? $status : '',
        ]);
    }

    // ─────────────────────────────────────────────
    //  Helper
    // ─────────────────────────────────────────────

    /**
     * Simpan kehadiran. Duplikat dicegah oleh unique key (id_sesi, nip),
     * jadi tap ganda bersamaan dari dua perangkat tetap tercatat sekali.
     */
    private function catatKehadiran(array $sesi, array $peserta, array $anggota, string $metode, string $foto64)
    {
        $idSesi = (int) $sesi['id'];
        $nip    = $peserta['nip'];

        $lama = $this->absensiModel->getByNipSesi($idSesi, $nip);
        if ($lama) {
            return $this->jsonSudahAbsen($lama, $peserta, $anggota);
        }

        try {
            $idAbsensi = $this->absensiModel->insert([
                'id_sesi'       => $idSesi,
                'id_anggota'    => (int) $anggota['id'],
                'nama_snapshot' => $anggota['nama'],
                'nip_snapshot'  => $nip,
                'waktu_absen'   => date('Y-m-d H:i:s'),
                'metode'        => $metode,
            ]);
        } catch (DatabaseException|\mysqli_sql_exception) {
            $idAbsensi = false;
        }
        if (! $idAbsensi) {
            $lama = $this->absensiModel->getByNipSesi($idSesi, $nip);

            return $lama
                ? $this->jsonSudahAbsen($lama, $peserta, $anggota)
                : $this->json('error', 'Gagal menyimpan absensi. Coba lagi.');
        }

        // Foto disimpan setelah data masuk, supaya tidak ada file yatim kalau insert gagal
        $fotoPath = str_starts_with($foto64, 'data:image') ? $this->simpanFoto($foto64, $nip, $idSesi) : null;
        if ($fotoPath) {
            $this->absensiModel->update($idAbsensi, ['foto_path' => $fotoPath]);
        }

        // Catat id ini milik session ini (syarat tombol Batalkan)
        $milik   = session()->get('absen_saya') ?? [];
        $milik[] = (int) $idAbsensi;
        session()->set('absen_saya', array_slice($milik, -20));

        $this->catatLog("Absensi: {$anggota['nama']} (NIP {$nip}) " . label_sesi($sesi['nama_sesi']) . " via {$metode}");

        return $this->json('success', 'Absensi tercatat.', [
            'id_absensi' => (int) $idAbsensi,
            'nama'       => $anggota['nama'],
            'nip'        => $nip,
            'departemen' => ($peserta['departemen'] ?: $anggota['departemen']) ?: '-',
            'section'    => ($peserta['section'] ?: $anggota['section']) ?: '-',
            'shift'      => ($peserta['shift'] ?: $anggota['shift']) ?: '-',
            'waktu'      => date('H:i:s'),
            'metode'     => $metode,
            'foto_path'  => $fotoPath,
            'batas_batal' => self::BATAS_BATAL,
            'jumlah'     => $this->absensiModel->countBySesi($idSesi),
            'total'      => $this->pesertaModel->totalPesertaSesi($idSesi),
            'lima'       => $this->getLimaTerakhir($idSesi),
        ]);
    }

    private function jsonSudahAbsen(array $lama, array $peserta, array $anggota)
    {
        return $this->json('error', "{$anggota['nama']} sudah absen di sesi ini.", [
            'kode'   => 'sudah_absen',
            'detail' => [
                'nama'       => $anggota['nama'],
                'nip'        => $peserta['nip'],
                'departemen' => ($peserta['departemen'] ?: $anggota['departemen']) ?: '-',
                'section'    => ($peserta['section'] ?: $anggota['section']) ?: '-',
                'shift'      => ($peserta['shift'] ?: $anggota['shift']) ?: '-',
                'waktu'      => substr($lama['waktu_absen'], 11, 8),
                'metode'     => $lama['metode'],
                'foto_path'  => $lama['foto_path'] ?: null,
            ],
        ]);
    }

    private function tolakBukanPeserta(array $sesi, string $nip, ?string $nama)
    {
        $siapa = $nama ? "{$nama} (NIP {$nip})" : "NIP {$nip}";

        return $this->json('error', "{$siapa} tidak terdaftar sebagai peserta " . label_sesi($sesi['nama_sesi']) . '.', ['kode' => 'bukan_peserta']);
    }

    private function json(string $status, string $message, array $extra = [])
    {
        return $this->response->setJSON(['status' => $status, 'message' => $message] + $extra);
    }

    /**
     * Pastikan sesi milik RAT yang dipakai, RAT masih aktif, dan sesi tidak terkunci.
     * Return array sesi, atau string pesan error.
     */
    private function cekSesiTerbuka(int $idSesi): array|string
    {
        $sesi = $this->sesiMilikRat($idSesi);
        if (! $sesi) {
            return 'Sesi tidak valid untuk RAT yang sedang berjalan. Muat ulang halaman absensi.';
        }
        if ($sesi['status'] === 'terkunci') {
            return label_sesi($sesi['nama_sesi']) . ' sudah dikunci.';
        }
        $rat = $this->ratModel->find((int) $sesi['id_rat']);
        if (! $rat || $rat['status'] !== 'aktif') {
            return 'RAT ini sudah selesai, absensi ditutup.';
        }

        return $sesi;
    }

    /**
     * Sesi yang dibuka saat halaman absensi diakses: sesi aktif yang jamnya
     * sedang berjalan, kalau tidak ada sesi aktif berikutnya, lalu sesi aktif pertama.
     */
    private function sesiSaatIni(array $daftarSesi): array
    {
        $sekarang = date('H:i:s');
        $aktif    = array_values(array_filter($daftarSesi, static fn ($s) => $s['status'] === 'aktif'));

        foreach ($aktif as $s) {
            if ($s['waktu_mulai'] && $s['waktu_selesai'] && $s['waktu_mulai'] <= $sekarang && $sekarang <= $s['waktu_selesai']) {
                return $s;
            }
        }
        foreach ($aktif as $s) {
            if ($s['waktu_mulai'] && $s['waktu_mulai'] > $sekarang) {
                return $s;
            }
        }

        return $aktif[0] ?? $daftarSesi[0];
    }

    private function simpanFoto(string $base64, string $nip, int $idSesi): ?string
    {
        $data = explode(',', $base64, 2);
        if (count($data) !== 2) {
            return null;
        }

        $decoded = base64_decode($data[1], true);
        // Pastikan isinya benar-benar gambar JPEG/PNG sebelum disimpan di folder publik
        $info = $decoded !== false ? @getimagesizefromstring($decoded) : false;
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return null;
        }

        $tanggal = date('Y-m-d');
        $dir     = FCPATH . 'uploads/absensi-foto/' . $tanggal . '/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = preg_replace('/[^0-9A-Za-z]/', '', $nip) . '_sesi' . $idSesi . '_' . date('His') . '_' . bin2hex(random_bytes(3)) . '.jpg';
        if (file_put_contents($dir . $filename, $decoded) === false) {
            return null;
        }

        return 'uploads/absensi-foto/' . $tanggal . '/' . $filename;
    }

    private function hapusFoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/absensi-foto/') && is_file(FCPATH . $path)) {
            unlink(FCPATH . $path);
        }
    }

    private function getLimaTerakhir(int $idSesi): array
    {
        $rows = db_connect()->query('
            SELECT ab.nama_snapshot, ab.nip_snapshot, ab.waktu_absen, ab.metode, ab.foto_path,
                   COALESCE(NULLIF(p.departemen, ""), NULLIF(a.departemen, ""), "-") AS departemen,
                   COALESCE(NULLIF(p.section, ""),    NULLIF(a.section, ""),    "-") AS section,
                   COALESCE(NULLIF(p.shift, ""),      NULLIF(a.shift, ""),      "-") AS shift
            FROM tb_absensi ab
            JOIN tb_peserta_rat p  ON p.nip = ab.nip_snapshot AND p.id_sesi = ab.id_sesi
            LEFT JOIN tb_anggota a ON a.id = ab.id_anggota
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
        ], $rows);
    }
}
