<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\OwnerController;
use Illuminate\Support\Facades\Route;

// Landing publik (coming soon).
Route::view('/', 'welcome');

// Autentikasi.
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Area terproteksi.
Route::middleware('auth')->group(function () {
    Route::get('/kasir', [KasirController::class, 'numpad'])->name('kasir');
    Route::post('/kasir/lookup', [KasirController::class, 'lookup'])->name('kasir.lookup');
    Route::get('/kasir/daftar', [KasirController::class, 'registerForm'])->name('kasir.register.form');
    Route::post('/kasir/daftar', [KasirController::class, 'register'])->name('kasir.register');
    Route::get('/kasir/pelanggan/{customer}', [KasirController::class, 'profile'])->name('kasir.profile');
    Route::post('/kasir/pelanggan/{customer}/stempel', [KasirController::class, 'stamp'])->name('kasir.stamp');
    Route::post('/kasir/pelanggan/{customer}/tukar', [KasirController::class, 'redeem'])->name('kasir.redeem');

    // Pengaturan owner.
    Route::get('/owner/pengaturan', [OwnerController::class, 'settings'])->name('owner.settings');
    Route::post('/owner/pengaturan', [OwnerController::class, 'updateSettings'])->name('owner.settings.update');
});
