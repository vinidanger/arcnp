<?php

use App\Domain\Hosting\Http\Controllers\Client\HostingAccountController;
use Illuminate\Support\Facades\Route;

Route::resource('hosting-accounts', HostingAccountController::class)->only(['index', 'show']);
Route::post('hosting-accounts/{hosting_account}/ssl', [HostingAccountController::class, 'issueSsl'])
    ->name('hosting-accounts.ssl.store');
Route::post('hosting-accounts/{hosting_account}/php-version', [HostingAccountController::class, 'changePhpVersion'])
    ->name('hosting-accounts.php-version.update');
Route::post('hosting-accounts/{hosting_account}/database', [HostingAccountController::class, 'createDatabase'])
    ->name('hosting-accounts.database.store');
Route::delete('hosting-accounts/{hosting_account}/database', [HostingAccountController::class, 'deleteDatabase'])
    ->name('hosting-accounts.database.destroy');
Route::post('hosting-accounts/{hosting_account}/domains', [HostingAccountController::class, 'storeDomain'])
    ->name('hosting-accounts.domains.store');
Route::delete('hosting-accounts/{hosting_account}/domains/{domain}', [HostingAccountController::class, 'destroyDomain'])
    ->name('hosting-accounts.domains.destroy');
