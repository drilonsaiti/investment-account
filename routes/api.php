<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/account', [ClientController::class, 'account'])->name('clients.account');

    Route::get('/clients/{client}/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/clients/{client}/transactions', [TransactionController::class, 'store'])->name('transactions.store');
});
