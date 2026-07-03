<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiakadController;
use App\Http\Controllers\DashboardController;

// Auth Routes
Route::get('/', [SiakadController::class, 'index'])->name('login');
Route::post('/login', [SiakadController::class, 'postLogin'])->name('login.post');
Route::get('/logout', [SiakadController::class, 'logout'])->name('logout');

// Group Routes yang Harus Login
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [DashboardController::class, 'profil'])->name('user.profil');
    Route::put('/profil/update', [DashboardController::class, 'updateProfil'])->name('user.profil.update');

    // Group Admin Master Data
    Route::prefix('admin')->group(function () {
        // Tampilan (GET)
        Route::get('/guru', [DashboardController::class, 'daftarGuru'])->name('admin.guru');
        Route::get('/siswa', [DashboardController::class, 'daftarSiswa'])->name('admin.siswa');
        Route::get('/kelas', [DashboardController::class, 'daftarKelas'])->name('admin.kelas');
        Route::get('/mapel', [DashboardController::class, 'daftarMapel'])->name('admin.mapel');
        Route::get('/plotting', [DashboardController::class, 'plottingSiswa'])->name('admin.plotting');
        Route::get('/jadwal', [DashboardController::class, 'daftarJadwal'])->name('admin.jadwal');
        Route::get('/laporan', [DashboardController::class, 'laporan'])->name('admin.laporan');
        Route::get('/admin/laporan/unduh-template/{kelas_id}', [DashboardController::class, 'unduhTemplateRapotKelas'])->name('admin.laporan.template_excel');
        Route::post('/admin/laporan/import-auto-save', [DashboardController::class, 'importRapotKelasAsync'])->name('admin.laporan.import_warmup');
        
        // PERBAIKAN SINKRONISASI: Pastikan name route-nya terdaftar murni seperti ini Jan
        Route::post('/admin/laporan/simpan-manual-rapot', [DashboardController::class, 'simpanManualRapotSiswa'])->name('admin.laporan.simpan_manual');

        // FITUR KHUSUS ADMIN ONLY
        Route::get('/leger-ujian', [DashboardController::class, 'lihatLegerUjianMurni'])->name('wali.leger_ujian');
        Route::get('/rekap-transkrip', [DashboardController::class, 'rekapTranskripHarian'])->name('wali.rekap_transkrip');
        
        // Route untuk cetak raport excel per siswa individual
        Route::get('/laporan/export-excel-siswa/{siswa_id}', [DashboardController::class, 'exportRapotSiswaExcel']) ->name('admin.laporan.export_excel_siswa');
        // Aksi Simpan/Update (POST)
        Route::post('/siswa/store', [DashboardController::class, 'storeSiswa'])->name('admin.siswa.store');
        Route::delete('/siswa/delete-all', [DashboardController::class, 'deleteAllSiswa'])->name('admin.siswa.delete_all');
        Route::post('/guru/store', [DashboardController::class, 'storeGuru'])->name('admin.guru.store');
        Route::post('/kelas/store', [DashboardController::class, 'storeKelas'])->name('admin.kelas.store');
        Route::post('/mapel/store', [DashboardController::class, 'storeMapel'])->name('admin.mapel.store');
        Route::put('/mapel/update/{id}', [DashboardController::class, 'updateMapel'])->name('admin.mapel.update');
        Route::delete('/mapel/delete/{id}', [DashboardController::class, 'deleteMapel'])->name('admin.mapel.delete');

        Route::get('/bab', [DashboardController::class, 'daftarBab'])->name('admin.bab');
        Route::post('/bab/store', [DashboardController::class, 'storeBab'])->name('admin.bab.store');
        Route::put('/bab/update/{id}', [DashboardController::class, 'updateBab'])->name('admin.bab.update');
        Route::delete('/bab/delete/{id}', [DashboardController::class, 'deleteBab'])->name('admin.bab.delete');
        
        Route::post('/admin/jadwal/store', [DashboardController::class, 'storeJadwal'])->name('admin.jadwal.store');
        Route::get('/admin/jadwal-ujian', [DashboardController::class, 'daftarJadwalUjian'])->name('admin.jadwal.ujian');
        Route::delete('/admin/jadwal/delete/{id}', [DashboardController::class, 'deleteJadwal'])->name('admin.jadwal.delete');
        Route::put('/admin/jadwal/update/{id}', [DashboardController::class, 'updateJadwal'])->name('admin.jadwal.update');
        Route::get('/get-guru-by-mapel/{mapel_id}', [DashboardController::class, 'getGuruByMapel']);
        Route::get('/admin/plotting-guru', [DashboardController::class, 'plottingGuru'])->name('admin.plotting.guru');
        Route::post('/admin/plotting-guru', [DashboardController::class, 'storePlottingGuru'])->name('admin.plotting.guru.store');
        Route::get('/admin/get-guru-by-mapel/{mapel_id}', [DashboardController::class, 'getGuruByMapel']);
        Route::post('/plotting/update', [DashboardController::class, 'updatePlotSiswa'])->name('admin.plotting.update');
        Route::put('/admin/user/update/{id}', [DashboardController::class, 'updateUser'])->name('admin.user.update');
        Route::delete('/admin/user/delete/{id}', [DashboardController::class, 'deleteUser'])->name('admin.user.delete');
        Route::delete('/admin/kelas/delete/{id}', [DashboardController::class, 'deleteKelas'])->name('admin.kelas.delete');
        Route::get('/admin/plotting/{kelas_id}', [DashboardController::class, 'detailPlotting'])->name('admin.plotting.detail');
        Route::post('/admin/plotting/remove/{siswa_id}', [DashboardController::class, 'hapusPlotSiswa'])->name('admin.plotting.remove');
        Route::get('/admin/plotting-walikelas', [DashboardController::class, 'plottingWaliKelas'])->name('admin.plotting.walikelas');
        Route::post('/admin/plotting-walikelas/update', [DashboardController::class, 'updatePlotWaliKelas'])->name('admin.plotting.walikelas.update');
        Route::post('/admin/plotting-walikelas/clear/{user_id}', [DashboardController::class, 'clearPlotWaliKelas'])->name('admin.plotting.walikelas.clear');
        // Jalur khusus Administrasi Ekspor-Impor Lembar Cetak Rapot Kelas Wali

    });

    // Action Input Nilai (Guru/Walikelas)
    Route::post('/nilai/harian/store', [DashboardController::class, 'storeNilaiHarian'])->name('nilai.harian.store');
    // Route Pendukung Ekspor-Impor Matriks Nilai Harian Siswa
    Route::get('/nilai/harian/unduh-template/{jadwal_id}', [DashboardController::class, 'unduhTemplateNilaiHarian'])->name('nilai.harian.template');
    Route::post('/nilai/harian/import-excel', [DashboardController::class, 'importExcelNilaiHarian'])->name('nilai.harian.import');
    Route::post('/nilai/uts-uas/store', [DashboardController::class, 'storeNilaiUtsUas'])->name('nilai.uts_uas.store');
    Route::get('/wali/rekap-nilai', [DashboardController::class, 'daftarSeluruhNilai'])->name('wali.rekap_nilai');
    Route::get('/wali/cetak-rapot', [DashboardController::class, 'halamanCetakRapot'])->name('wali.cetak_rapot');
    Route::get('/wali/daftar-nilai', [DashboardController::class, 'daftarNilaiWali'])->name('wali.daftar_nilai');
    Route::get('/wali/get-riwayat-nilai/{mapel}/{kelas}/{jenis}', [DashboardController::class, 'getRiwayatNilai']);
    Route::get('/wali/leger', [DashboardController::class, 'lihatLegerWali'])->name('wali.leger');
    Route::get('/nilai/rekap-akhir-mapel/{mapel_id}/{kelas_id}', [DashboardController::class, 'rekapNilaiAkhirMapel'])->name('nilai.rekap_akhir_mapel');
    // Route Khusus Manajemen KKM Guru Pengampu
    Route::get('/guru/kkm', [DashboardController::class, 'halamanKkmGuru'])->name('guru.kkm.index');
    Route::post('/guru/kkm/store', [DashboardController::class, 'storeKkmGuru'])->name('guru.kkm.store');
    // Jalur khusus untuk membaca data excel tanpa menyimpan ke database
    Route::post('/nilai/harian/baca-excel', [DashboardController::class, 'importExcelNilaiHarianAsync'])->name('nilai.harian.baca_excel');
});

Route::get('/generate-symlink', function () {
    $targetFolder = base_path('storage/app/public/profil');
    $linkFolder = $_SERVER['DOCUMENT_ROOT'] . '/storage';
    if (!file_exists($targetFolder)) { mkdir($targetFolder, 0755, true); }
    if (file_exists($linkFolder) || is_link($linkFolder)) {
        if (is_link($linkFolder)) { unlink($linkFolder); } else { rename($linkFolder, $linkFolder . '_backup_' . time()); }
    }
    if (symlink(base_path('storage/app/public'), $linkFolder)) {
        return "<h1 style='color:green;'>✓ Sukses! jalur pintas (Symlink) baru berhasil dibuat penuh.</h1>";
    } else {
        return "<h1 style='color:red;'>X Gagal! Server menolak pembuatan jalur pintas otomatis.</h1>";
    }
});