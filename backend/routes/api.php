<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiswaController;
use App\Http\Controllers\Api\TutorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SOBAT PELAJAR
|--------------------------------------------------------------------------
*/

// PUBLIC ROUTES (Tanpa Login)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// PROTECTED ROUTES (Butuh Token Bearer)
Route::middleware('auth:sanctum')->group(function () {

    // Logout & Get User Info
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return response()->json(['status' => 'success', 'data' => $request->user()]);
    });

    // ==================== ADMIN ROUTES ====================
    Route::prefix('admin')->group(function () {

        // Dashboard Stats
        Route::get('/dashboard', [AdminController::class, 'index']);
        Route::get('/chart-pendapatan', [AdminController::class, 'chartPendapatan']);
        Route::get('/laporan-terbaru', [AdminController::class, 'laporanTerbaru']);
        Route::get('/transaksi-terbaru', [AdminController::class, 'transaksiTerbaru']);

        // CRUD Siswa
        Route::get('/siswa', [AdminController::class, 'dataSiswa']);
        Route::get('/siswa/{id}', [AdminController::class, 'getSiswaById']);
        Route::post('/siswa', [AdminController::class, 'tambahSiswa']);
        Route::put('/siswa/{id}', [AdminController::class, 'updateSiswa']);
        Route::delete('/siswa/{id}', [AdminController::class, 'hapusSiswa']);

        // CRUD Tutor
        Route::get('/tutor', [AdminController::class, 'dataTutor']);
        Route::get('/tutor/{id}', [AdminController::class, 'getTutorById']);
        Route::post('/tutor', [AdminController::class, 'tambahTutor']);
        Route::put('/tutor/{id}', [AdminController::class, 'updateTutor']);
        Route::delete('/tutor/{id}', [AdminController::class, 'hapusTutor']);

        // CRUD Kelas
        Route::get('/kelas', [AdminController::class, 'dataKelas']);
        Route::get('/kelas/{id}', [AdminController::class, 'getKelasById']);
        Route::post('/kelas', [AdminController::class, 'tambahKelas']);
        Route::put('/kelas/{id}', [AdminController::class, 'updateKelas']);
        Route::delete('/kelas/{id}', [AdminController::class, 'hapusKelas']);

        // Laporan/Complaints
        Route::get('/laporan', [AdminController::class, 'getLaporan']);
        Route::get('/laporan/{id}', [AdminController::class, 'getLaporanById']);
        Route::put('/laporan/{id}/status', [AdminController::class, 'updateStatusLaporan']);
        Route::delete('/laporan/{id}', [AdminController::class, 'deleteLaporan']);

        // Transaksi
        Route::get('/transaksi', [AdminController::class, 'getTransaksi']);
        Route::get('/transaksi/{id}', [AdminController::class, 'getTransaksiById']);
        Route::put('/transaksi/{id}/status', [AdminController::class, 'updateStatusTransaksi']);
        Route::get('/transaksi/export', [AdminController::class, 'exportTransaksi']);
    });

    // ==================== SISWA ROUTES ====================
    Route::prefix('siswa')->group(function () {

        // Dashboard & Profil
        Route::get('/dashboard', [SiswaController::class, 'index']);
        Route::get('/profil', [SiswaController::class, 'profil']);
        Route::put('/profil', [SiswaController::class, 'updateProfil']);

        // Cari Tutor
        Route::get('/cari-tutor', [SiswaController::class, 'cariTutor']); // 
        Route::get('/tutor/{id}', [SiswaController::class, 'getTutorById']);

        // Pesanan
        Route::get('/pesanan', [SiswaController::class, 'getPesanan']); // 
        Route::get('/pesanan/{id}', [SiswaController::class, 'getPesananById']);
        Route::post('/pesanan', [SiswaController::class, 'buatPesanan']); // 
        Route::post('/pesanan/{id}/cancel', [SiswaController::class, 'cancelPesanan']);
        Route::get('/riwayat', [SiswaController::class, 'riwayatPesanan']);

        // Materi & Jadwal
        Route::get('/materi', [SiswaController::class, 'getMateri']);
        Route::get('/materi/{id}/download', [SiswaController::class, 'downloadMateri']);
        Route::post('/materi/{id}/read', [SiswaController::class, 'markAsRead']);
        Route::get('/jadwal', [SiswaController::class, 'getJadwal']);
        Route::get('/jadwal/{id}', [SiswaController::class, 'getJadwalById']);

        // Progres
        Route::get('/progress', [SiswaController::class, 'getProgress']);
    });

    // ==================== TUTOR ROUTES ====================
    Route::prefix('tutor')->group(function () {

        // Dashboard & Profil
        Route::get('/dashboard', [TutorController::class, 'index']);
        Route::get('/profil', [TutorController::class, 'profil']);
        Route::put('/profil', [TutorController::class, 'updateProfil']);
        Route::get('/ringkasan', [TutorController::class, 'getRingkasan']);

        // Pesanan
        Route::get('/pesanan', [TutorController::class, 'daftarPesanan']);
        Route::get('/pesanan/{id}', [TutorController::class, 'getPesananById']);
        Route::put('/pesanan/{id}/status', [TutorController::class, 'updateStatusPesanan']);

        // Siswa & Jadwal
        Route::get('/daftar-siswa', [TutorController::class, 'getDaftarSiswa']);
        Route::get('/daftar-siswa/{id}', [TutorController::class, 'getSiswaDetail']);
        Route::get('/jadwal', [TutorController::class, 'getJadwalMengajar']);
        Route::put('/jadwal', [TutorController::class, 'updateJadwal']);

        // Kelas & Pendapatan
        Route::get('/kelas', [TutorController::class, 'getKelas']);
        Route::post('/kelas', [TutorController::class, 'createKelas']);
        Route::put('/kelas/{id}', [TutorController::class, 'updateKelas']);
        Route::delete('/kelas/{id}', [TutorController::class, 'deleteKelas']);
        Route::get('/pendapatan', [TutorController::class, 'getPendapatan']);
        Route::post('/withdraw', [TutorController::class, 'requestWithdraw']);

        // Materi
        Route::post('/materi', [TutorController::class, 'uploadMateri']);
        Route::get('/materi', [TutorController::class, 'getMateri']);
        Route::delete('/materi/{id}', [TutorController::class, 'deleteMateri']);
    });
});
