<?php

use App\Http\Controllers\DonationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\MaxiCashWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/live', [LiveController::class, 'show'])->name('live.show');
Route::get('/live/status', [LiveController::class, 'status'])->name('live.status');

Route::get('/soutenir', [DonationController::class, 'index'])->name('donations.index');
Route::post('/soutenir', [DonationController::class, 'store'])->name('donations.store');
Route::get('/soutenir/retour', [DonationController::class, 'return'])->name('donations.return');
Route::get('/soutenir/paiement/reussi', [DonationController::class, 'success'])->name('donations.success');
Route::get('/soutenir/paiement/echec', [DonationController::class, 'failure'])->name('donations.failure');
Route::get('/soutenir/paiement/en-attente', [DonationController::class, 'pending'])->name('donations.pending');

Route::permanentRedirect('/offrandes', '/soutenir');
Route::post('/offrandes', [DonationController::class, 'store']);
Route::get('/offrandes/retour', function () {
    return redirect()->route('donations.return', ['public_id' => request()->query('public_id')], 301);
});

Route::match(['get', 'post'], '/webhooks/maxicash', MaxiCashWebhookController::class)->name('webhooks.maxicash');
