<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\OwnerBranchController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\OwnerProgramController;
use App\Http\Controllers\OwnerStoreController;
use Illuminate\Support\Facades\Route;

// Landing publik (coming soon).
Route::view('/', 'welcome');

// Autentikasi.
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Area terproteksi.
Route::middleware('auth')->group(function () {
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

        Route::get('/toko', [OwnerStoreController::class, 'edit'])->name('store');
        Route::post('/toko', [OwnerStoreController::class, 'update'])->name('store.update');

        Route::get('/outlet', [OwnerBranchController::class, 'index'])->name('branches');
        Route::post('/outlet', [OwnerBranchController::class, 'store'])->name('branches.store');
        Route::put('/outlet/{branch}', [OwnerBranchController::class, 'update'])->name('branches.update');
        Route::delete('/outlet/{branch}', [OwnerBranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('/program', [OwnerProgramController::class, 'edit'])->name('program');
        Route::post('/program', [OwnerProgramController::class, 'update'])->name('program.update');
        Route::post('/program/hadiah', [OwnerProgramController::class, 'storeReward'])->name('program.reward.store');
        Route::put('/program/hadiah/{reward}', [OwnerProgramController::class, 'updateReward'])->name('program.reward.update');
        Route::delete('/program/hadiah/{reward}', [OwnerProgramController::class, 'destroyReward'])->name('program.reward.destroy');
    });
});
