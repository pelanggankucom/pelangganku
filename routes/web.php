<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FinanceSubscriptionController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\OwnerBranchController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\OwnerProgramController;
use App\Http\Controllers\OwnerStoreController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PrinterSettingsController;
use App\Http\Controllers\PosMenuController;
use App\Http\Controllers\PosSubscriptionController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

// Landing publik (coming soon).
Route::view('/', 'welcome');

// Webhook DOKU — harus publik (tanpa auth), CSRF dikecualikan via controller
Route::post('/webhook/pos/doku', [PosSubscriptionController::class, 'webhook'])->name('pos.webhook');
Route::post('/webhook/laporan/doku', [FinanceSubscriptionController::class, 'webhook'])->name('finance.webhook');

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

// Super Admin.
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/owner', [SuperAdminController::class, 'owners'])->name('owners');
    Route::get('/kasir', [SuperAdminController::class, 'kasir'])->name('kasir');
    Route::get('/merchant', [SuperAdminController::class, 'merchants'])->name('merchants');
    Route::post('/merchant/{merchant}/pos', [SuperAdminController::class, 'togglePos'])->name('merchant.pos.toggle');
    Route::put('/merchant/{merchant}/pos', [SuperAdminController::class, 'updatePosExpiry'])->name('merchant.pos.expiry');
    Route::post('/merchant/{merchant}/finance', [SuperAdminController::class, 'toggleFinance'])->name('merchant.finance.toggle');
    Route::put('/merchant/{merchant}/finance', [SuperAdminController::class, 'updateFinanceExpiry'])->name('merchant.finance.expiry');
    Route::post('/user/{user}/toggle', [SuperAdminController::class, 'toggleUser'])->name('user.toggle');
    Route::delete('/user/{user}', [SuperAdminController::class, 'deleteUser'])->name('user.delete');
});

// Area terproteksi.
Route::middleware('auth')->group(function () {
    // Merchant selection
    Route::get('/pilih-toko', [MerchantController::class, 'select'])->name('merchant.select');
    Route::post('/pilih-toko', [MerchantController::class, 'switch'])->name('merchant.switch');

    // Kasir — POS.
    Route::get('/kasir/pos', [PosController::class, 'show'])->name('kasir.pos');
    Route::post('/kasir/pos/transaksi', [PosController::class, 'store'])->name('kasir.pos.store');

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

        // Pengaturan printer
        Route::get('/printer', [PrinterSettingsController::class, 'edit'])->name('printer');
        Route::post('/printer', [PrinterSettingsController::class, 'update'])->name('printer.update');

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

        // POS berlangganan
        Route::get('/pos', [PosSubscriptionController::class, 'show'])->name('pos');
        Route::post('/pos/berlangganan', [PosSubscriptionController::class, 'subscribe'])->name('pos.subscribe');
        Route::post('/pos/trial', [PosSubscriptionController::class, 'activateTrial'])->name('pos.trial');
        Route::get('/pos/kembali', [PosSubscriptionController::class, 'return'])->name('pos.return');
        Route::get('/pos/riwayat', [OwnerController::class, 'posHistory'])->name('pos.history');
        Route::get('/pos/riwayat/export', [OwnerController::class, 'exportPosHistory'])->name('pos.history.export');

        // POS menu
        Route::get('/pos/menu', [PosMenuController::class, 'index'])->name('pos.menu');
        Route::post('/pos/menu', [PosMenuController::class, 'store'])->name('pos.menu.store');
        Route::put('/pos/menu/{item}', [PosMenuController::class, 'update'])->name('pos.menu.update');
        Route::delete('/pos/menu/{item}', [PosMenuController::class, 'destroy'])->name('pos.menu.destroy');

        // Laporan Keuangan
        Route::get('/laporan', [FinanceController::class, 'index'])->name('laporan');
        Route::get('/laporan/langganan', [FinanceSubscriptionController::class, 'show'])->name('laporan.sub');
        Route::post('/laporan/langganan', [FinanceSubscriptionController::class, 'subscribe'])->name('laporan.subscribe');
        Route::post('/laporan/trial', [FinanceSubscriptionController::class, 'activateTrial'])->name('laporan.trial');
        Route::get('/laporan/kembali', [FinanceSubscriptionController::class, 'return'])->name('laporan.return');
        Route::post('/laporan/entry', [FinanceController::class, 'storeEntry'])->name('laporan.entry.store');
        Route::delete('/laporan/entry/{entry}', [FinanceController::class, 'destroyEntry'])->name('laporan.entry.destroy');
        Route::get('/laporan/export', [FinanceController::class, 'export'])->name('laporan.export');
    });
});
