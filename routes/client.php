<?php

use App\Domain\Hosting\Http\Controllers\Client\HostingAccountController;
use Illuminate\Support\Facades\Route;

Route::resource('hosting-accounts', HostingAccountController::class)->only(['index', 'show']);
Route::post('hosting-accounts/{hosting_account}/ssl', [HostingAccountController::class, 'issueSsl'])
    ->name('hosting-accounts.ssl.store');
Route::post('hosting-accounts/{hosting_account}/php-version', [HostingAccountController::class, 'changePhpVersion'])
    ->name('hosting-accounts.php-version.update');
Route::post('hosting-accounts/{hosting_account}/databases', [HostingAccountController::class, 'createDatabase'])
    ->name('hosting-accounts.databases.store');
Route::delete('hosting-accounts/{hosting_account}/databases/{database}', [HostingAccountController::class, 'deleteDatabase'])
    ->name('hosting-accounts.databases.destroy');
Route::get('hosting-accounts/{hosting_account}/databases/{database}/phpmyadmin', [HostingAccountController::class, 'phpMyAdminSso'])
    ->name('hosting-accounts.databases.phpmyadmin');
Route::post('hosting-accounts/{hosting_account}/domains', [HostingAccountController::class, 'storeDomain'])
    ->name('hosting-accounts.domains.store');
Route::delete('hosting-accounts/{hosting_account}/domains/{domain}', [HostingAccountController::class, 'destroyDomain'])
    ->name('hosting-accounts.domains.destroy');
Route::post('hosting-accounts/{hosting_account}/backup-frequency', [HostingAccountController::class, 'updateBackupFrequency'])
    ->name('hosting-accounts.backup-frequency.update');
Route::post('hosting-accounts/{hosting_account}/backups', [HostingAccountController::class, 'createBackup'])
    ->name('hosting-accounts.backups.store');
Route::get('hosting-accounts/{hosting_account}/backups/{backup}/{filename}', [HostingAccountController::class, 'downloadBackup'])
    ->name('hosting-accounts.backups.download');
