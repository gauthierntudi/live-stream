<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DonationController;
use App\Http\Controllers\Admin\LiveController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::redirect('/', '/admin/paiements')->name('dashboard');

    Route::get('paiements', [DonationController::class, 'index'])->name('donations.index');
    Route::get('paiements/{donation}', [DonationController::class, 'show'])->name('donations.show');

    Route::get('live', [LiveController::class, 'index'])->name('live.index');
    Route::post('live/fournisseur', [LiveController::class, 'updateProvider'])->name('live.provider.update');
    Route::post('live/youtube-video', [LiveController::class, 'updateYoutubeLiveVideo'])->name('live.youtube.update');
    Route::post('live/fournisseur/env', [LiveController::class, 'resetProviderToEnv'])->name('live.provider.reset');
    Route::post('live/creer', [LiveController::class, 'store'])->name('live.store');
    Route::get('live/preview-status', [LiveController::class, 'previewStatus'])->name('live.preview-status');
    Route::post('live/demarrer', [LiveController::class, 'start'])->name('live.start');
    Route::post('live/publier', [LiveController::class, 'publish'])->name('live.publish');
    Route::post('live/masquer-public', [LiveController::class, 'unpublish'])->name('live.unpublish');
    Route::post('live/arreter', [LiveController::class, 'stop'])->name('live.stop');
});
