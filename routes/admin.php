<?php

use App\Domain\Hosting\Http\Controllers\Admin\HostingAccountController;
use App\Domain\Hosting\Http\Controllers\Admin\PlanController;
use App\Domain\Servers\Http\Controllers\Admin\ServerController;
use Illuminate\Support\Facades\Route;

Route::resource('servers', ServerController::class);
Route::post('servers/{server}/regenerate-credential', [ServerController::class, 'regenerateCredential'])
    ->name('servers.regenerate-credential');
Route::post('servers/{server}/test-connection', [ServerController::class, 'testConnection'])
    ->name('servers.test-connection');

Route::resource('plans', PlanController::class)->except(['show']);

Route::resource('hosting-accounts', HostingAccountController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::post('hosting-accounts/{hosting_account}/retry', [HostingAccountController::class, 'retry'])
    ->name('hosting-accounts.retry');
