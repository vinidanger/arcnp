<?php

use App\Domain\Clients\Http\Controllers\Admin\ClientController;
use App\Domain\Hosting\Http\Controllers\Admin\HostingAccountController;
use App\Domain\Hosting\Http\Controllers\Admin\PlanController;
use App\Domain\Servers\Http\Controllers\Admin\ServerController;
use Illuminate\Support\Facades\Route;

Route::resource('clients', ClientController::class)->except(['show', 'destroy']);

Route::resource('servers', ServerController::class);
Route::post('servers/{server}/regenerate-credential', [ServerController::class, 'regenerateCredential'])
    ->name('servers.regenerate-credential');
Route::post('servers/{server}/test-connection', [ServerController::class, 'testConnection'])
    ->name('servers.test-connection');

Route::resource('plans', PlanController::class)->except(['show']);

Route::resource('hosting-accounts', HostingAccountController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::post('hosting-accounts/{hosting_account}/retry', [HostingAccountController::class, 'retry'])
    ->name('hosting-accounts.retry');
Route::post('hosting-accounts/{hosting_account}/suspend', [HostingAccountController::class, 'suspend'])
    ->name('hosting-accounts.suspend');
Route::post('hosting-accounts/{hosting_account}/reactivate', [HostingAccountController::class, 'reactivate'])
    ->name('hosting-accounts.reactivate');
Route::post('hosting-accounts/{hosting_account}/database', [HostingAccountController::class, 'createDatabase'])
    ->name('hosting-accounts.database.store');
Route::delete('hosting-accounts/{hosting_account}/database', [HostingAccountController::class, 'deleteDatabase'])
    ->name('hosting-accounts.database.destroy');
Route::post('hosting-accounts/{hosting_account}/ssl', [HostingAccountController::class, 'issueSsl'])
    ->name('hosting-accounts.ssl.store');
Route::post('hosting-accounts/{hosting_account}/domains', [HostingAccountController::class, 'storeDomain'])
    ->name('hosting-accounts.domains.store');
Route::delete('hosting-accounts/{hosting_account}/domains/{domain}', [HostingAccountController::class, 'destroyDomain'])
    ->name('hosting-accounts.domains.destroy');
