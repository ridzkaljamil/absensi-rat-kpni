<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Halaman default — redirect ke /login atau /dashboard
$routes->get('/', static function () {
    return redirect()->to(session()->get('isLoggedIn') ? '/dashboard' : '/login');
});

// ── Auth ──────────────────────────────────────────────────────
$routes->get('/login',           'AuthController::loginPage');
$routes->post('/login',          'AuthController::doLogin');
$routes->get('/logout',          'AuthController::logout');
$routes->get('/ganti-password',  'AuthController::changePasswordPage',  ['filter' => 'auth']);
$routes->post('/ganti-password', 'AuthController::doChangePassword',    ['filter' => 'auth']);

// ── Dashboard ─────────────────────────────────────────────────
$routes->get('/dashboard',         'DashboardController::index',   ['filter' => 'auth']);
$routes->get('/dashboard/polling', 'DashboardController::polling', ['filter' => 'auth']);

// ── RAT ───────────────────────────────────────────────────────
$routes->get('/rat',                      'RatController::index',      ['filter' => 'auth:admin']);
$routes->post('/rat',                     'RatController::store',      ['filter' => 'auth:admin']);
$routes->get('/rat/pilih/(:num)',          'RatController::pilih/$1',   ['filter' => 'auth:admin']);
$routes->post('/rat/(:num)/selesaikan',   'RatController::selesaikan/$1', ['filter' => 'auth:admin']);
$routes->post('/rat/(:num)/hapus',        'RatController::hapus/$1',   ['filter' => 'auth:admin']);

// ── Anggota ───────────────────────────────────────────────────
$routes->get('/anggota',                      'AnggotaController::index',           ['filter' => 'auth:admin']);
$routes->post('/anggota',                     'AnggotaController::store',           ['filter' => 'auth:admin']);
$routes->post('/anggota/import',              'AnggotaController::import',          ['filter' => 'auth:admin']);
$routes->get('/anggota/export',               'AnggotaController::export',          ['filter' => 'auth:admin']);
$routes->post('/anggota/hapus-semua',         'AnggotaController::hapusSemua',      ['filter' => 'auth:admin']);
$routes->post('/anggota/(:num)',              'AnggotaController::update/$1',       ['filter' => 'auth:admin']);
$routes->post('/anggota/(:num)/rfid',         'AnggotaController::updateRfid/$1',   ['filter' => 'auth:admin']);
$routes->post('/anggota/(:num)/hapus',        'AnggotaController::hapus/$1',        ['filter' => 'auth:admin']);
$routes->post('/anggota/(:num)/nonaktifkan',  'AnggotaController::nonaktifkan/$1',  ['filter' => 'auth:admin']);
$routes->post('/anggota/(:num)/aktifkan',     'AnggotaController::aktifkanKembali/$1', ['filter' => 'auth:admin']);

// ── Sesi ──────────────────────────────────────────────────────
$routes->get('/sesi',                 'SesiController::index',    ['filter' => 'auth:admin']);
$routes->post('/sesi',                'SesiController::store',    ['filter' => 'auth:admin']);
$routes->post('/sesi/(:num)',         'SesiController::update/$1',['filter' => 'auth:admin']);
$routes->post('/sesi/(:num)/hapus',   'SesiController::hapus/$1', ['filter' => 'auth:admin']);
$routes->post('/sesi/(:num)/lock',    'SesiController::lock/$1',  ['filter' => 'auth:admin']);
$routes->post('/sesi/(:num)/unlock',  'SesiController::unlock/$1',['filter' => 'auth:admin']);

// ── Anggota Bulk RFID
$routes->get('/anggota/bulk-rfid',           'AnggotaController::bulkRfid',         ['filter' => 'auth:admin']);
$routes->post('/anggota/bulk-rfid-save',     'AnggotaController::bulkRfidSave',     ['filter' => 'auth:admin']);

// ── Peserta RAT ───────────────────────────────────────────────
$routes->get('/peserta-rat',                  'PesertaRatController::index',         ['filter' => 'auth:admin']);
$routes->post('/peserta-rat/import',          'PesertaRatController::import',        ['filter' => 'auth:admin']);
$routes->post('/peserta-rat/store',           'PesertaRatController::store',         ['filter' => 'auth:admin']);
$routes->post('/peserta-rat/(:num)/rfid',     'PesertaRatController::daftarRfid/$1', ['filter' => 'auth:admin']);
$routes->post('/peserta-rat/(:num)/hapus',    'PesertaRatController::hapus/$1',      ['filter' => 'auth:admin']);
$routes->post('/peserta-rat/hapus-semua',     'PesertaRatController::hapusSemua',    ['filter' => 'auth:admin']);
$routes->get('/peserta-rat/bulk-rfid',       'PesertaRatController::bulkRfid',      ['filter' => 'auth:admin']);
$routes->post('/peserta-rat/bulk-rfid-save',  'PesertaRatController::bulkRfidSave',  ['filter' => 'auth:admin']);
$routes->post('/peserta-rat/salin-kehadiran', 'PesertaRatController::salinKehadiran', ['filter' => 'auth:admin']);
$routes->get('/peserta-rat/export',           'PesertaRatController::export',        ['filter' => 'auth:admin']);
$routes->get('/peserta-rat/lookup-nip',       'PesertaRatController::lookupNip',     ['filter' => 'auth:admin']);

// ── Absensi ───────────────────────────────────────────────────
$routes->get('/absensi',                   'AbsensiController::index',               ['filter' => 'auth']);
$routes->get('/absensi/sesi/(:num)',       'AbsensiController::pilihSesi/$1',        ['filter' => 'auth']);
$routes->post('/absensi/proses',           'AbsensiController::proses',              ['filter' => 'auth']);
$routes->get('/absensi/lima-terakhir',     'AbsensiController::limaTerakhir',          ['filter' => 'auth']);
$routes->post('/absensi/batalkan',        'AbsensiController::batalkan',              ['filter' => 'auth']);
$routes->post('/absensi/konfirmasi-induk', 'AbsensiController::prosesKonfirmasiInduk', ['filter' => 'auth']);

// ── Rekap ─────────────────────────────────────────────────────
$routes->get('/rekap',              'RekapController::index',           ['filter' => 'auth:admin']);
$routes->get('/rekap/sesi/(:num)', 'RekapController::downloadSesi/$1', ['filter' => 'auth:admin']);
$routes->get('/rekap/gabungan',    'RekapController::downloadGabungan',['filter' => 'auth:admin']);
$routes->get('/rekap/foto-sesi/(:num)',    'RekapController::exportFoto/$1',           ['filter' => 'auth:admin']);
$routes->get('/rekap/cetak',       'RekapController::cetak',           ['filter' => 'auth:admin']);
$routes->get('/rekap/backup/sql',  'RekapController::backupSql',       ['filter' => 'auth:admin']);
$routes->get('/rekap/backup/excel','RekapController::backupExcel',     ['filter' => 'auth:admin']);


// ── Doorprize ─────────────────────────────────────────────────
$routes->get('/doorprize',                   'DoorprizeController::index',         ['filter' => 'auth:admin']);
$routes->post('/doorprize/item/store',       'DoorprizeController::storeItem',     ['filter' => 'auth:admin']);
$routes->post('/doorprize/item/(:num)/update','DoorprizeController::updateItem/$1',['filter' => 'auth:admin']);
$routes->post('/doorprize/item/(:num)/hapus','DoorprizeController::hapusItem/$1', ['filter' => 'auth:admin']);
$routes->post('/doorprize/pick',             'DoorprizeController::pick',          ['filter' => 'auth:admin']);
$routes->post('/doorprize/winner/(:num)/hapus','DoorprizeController::hapusWinner/$1',['filter' => 'auth:admin']);
$routes->get('/doorprize/export-winners',    'DoorprizeController::exportWinners', ['filter' => 'auth:admin']);

// ── Log Aktivitas ─────────────────────────────────────────────
$routes->get('/log',            'LogAktivitasController::index',    ['filter' => 'auth:admin']);
$routes->post('/log/bersihkan', 'LogAktivitasController::bersihkan',['filter' => 'auth:admin']);

// ── Kelola User ───────────────────────────────────────────────
$routes->get('/users',               'UserController::index',    ['filter' => 'auth:admin']);
$routes->post('/users',              'UserController::store',    ['filter' => 'auth:admin']);
$routes->post('/users/(:num)',       'UserController::update/$1',['filter' => 'auth:admin']);
$routes->post('/users/(:num)/hapus', 'UserController::hapus/$1',['filter' => 'auth:admin']);
