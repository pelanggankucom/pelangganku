<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\OwnerBranchController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\OwnerProgramController;
use App\Http\Controllers\OwnerStoreController;
use Illuminate\Support\Facades\Route;

// Landing publik (coming soon).
Route::view('/', 'welcome');

// Autentikasi terpadu (owner / kasir / pelanggan) berbasis nomor HP + OTP WhatsApp.
Route::get('/masuk', [AccessController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AccessController::class, 'login']);
Route::get('/daftar', [AccessController::class, 'showRegister'])->name('register');
Route::post('/daftar', [AccessController::class, 'register']);
Route::get('/daftar/verifikasi-otp', [AccessController::class, 'showRegisterOtp'])->name('register.otp.show');
Route::post('/daftar/verifikasi-otp', [AccessController::class, 'registerOtpVerify'])->name('register.otp.verify');
Route::get('/lupa-password', [AccessController::class, 'showForgot'])->name('password.request');
Route::post('/lupa-password', [AccessController::class, 'forgot']);
Route::get('/lupa-password/verifikasi-otp', [AccessController::class, 'showForgotOtp'])->name('forgot.otp.show');
Route::post('/lupa-password/verifikasi-otp', [AccessController::class, 'forgotOtpVerify'])->name('forgot.otp.verify');
Route::post('/keluar', [AccessController::class, 'logout'])->name('logout');

// Kompatibilitas tautan lama.
Route::redirect('/login', '/masuk');
Route::redirect('/member/masuk', '/masuk');
Route::redirect('/member/daftar', '/daftar');

// Area pelanggan (member).
Route::middleware('auth:customer')->prefix('member')->name('member.')->group(function () {
    Route::get('/', [CustomerController::class, 'dashboard'])->name('dashboard');
    Route::get('/riwayat', [CustomerController::class, 'history'])->name('history');
    Route::post('/keluar', [AccessController::class, 'logout'])->name('logout');
});

// Area terproteksi.
Route::middleware('auth')->group(function () {
    // Merchant selection
    Route::get('/pilih-toko', [MerchantController::class, 'select'])->name('merchant.select');
    Route::post('/pilih-toko', [MerchantController::class, 'switch'])->name('merchant.switch');

    // Kasir.
    Route::get('/kasir', [KasirController::class, 'numpad'])->name('kasir');
    Route::post('/kasir/lookup', [KasirController::class, 'lookup'])->name('kasir.lookup');
    Route::get('/kasir/daftar', [KasirController::class, 'registerForm'])->name('kasir.register.form');
    Route::post('/kasir/daftar', [KasirController::class, 'register'])->name('kasir.register');
    Route::get('/kasir/pelanggan/{customer}', [KasirController::class, 'profile'])->name('kasir.profile');
    Route::post('/kasir/pelanggan/{customer}/stempel', [KasirController::class, 'stamp'])->name('kasir.stamp');
    Route::post('/kasir/pelanggan/{customer}/tukar', [KasirController::class, 'redeem'])->name('kasir.redeem');
    Route::post('/kasir/pelanggan/{customer}/reset', [KasirController::class, 'reset'])->name('kasir.reset');

    // Owner.
    Route::middleware('owner')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/', [OwnerController::class, 'dashboard'])->name('dashboard');

        // Daftar pelanggan
        Route::get('/pelanggan', [OwnerController::class, 'customers'])->name('customers');

        // Atur — menu utama
        Route::get('/atur', [OwnerController::class, 'settings'])->name('settings');

        // Profil akun owner
        Route::get('/profil-saya', [OwnerController::class, 'profile'])->name('profile');
        Route::post('/profil-saya', [OwnerController::class, 'updateProfile'])->name('profile.update');

        // Profil toko
        Route::get('/toko', [OwnerStoreController::class, 'edit'])->name('store');
        Route::post('/toko', [OwnerStoreController::class, 'update'])->name('store.update');

        // Outlet / cabang
        Route::get('/outlet', [OwnerBranchController::class, 'index'])->name('branches');
        Route::post('/outlet', [OwnerBranchController::class, 'store'])->name('branches.store');
        Route::put('/outlet/{branch}', [OwnerBranchController::class, 'update'])->name('branches.update');
        Route::delete('/outlet/{branch}', [OwnerBranchController::class, 'destroy'])->name('branches.destroy');

        // Hadiah & stempel
        Route::get('/program', [OwnerProgramController::class, 'edit'])->name('program');
        Route::post('/program', [OwnerProgramController::class, 'update'])->name('program.update');
        Route::post('/program/hadiah', [OwnerProgramController::class, 'storeReward'])->name('program.reward.store');
        Route::put('/program/hadiah/{reward}', [OwnerProgramController::class, 'updateReward'])->name('program.reward.update');
        Route::delete('/program/hadiah/{reward}', [OwnerProgramController::class, 'destroyReward'])->name('program.reward.destroy');

        // Pegawai kasir (dikelola di dalam halaman Outlet)
        Route::post('/kasir', [OwnerController::class, 'storeCashier'])->name('cashiers.store');
        Route::delete('/kasir/{user}', [OwnerController::class, 'destroyCashier'])->name('cashiers.destroy');
    });
});
