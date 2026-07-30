<?php

use App\Domain\Clients\Http\Controllers\Admin\ClientController;
use App\Domain\Hosting\Http\Controllers\Admin\CronJobController;
use App\Domain\Hosting\Http\Controllers\Admin\DnsZoneController;
use App\Domain\Hosting\Http\Controllers\Admin\FileManagerController;
use App\Domain\Hosting\Http\Controllers\Admin\MailDomainController;
use App\Domain\Hosting\Http\Controllers\Admin\HostingAccountController;
use App\Domain\Hosting\Http\Controllers\Admin\PlanController;
use App\Domain\Hosting\Http\Controllers\Admin\SshAccessController;
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
Route::post('hosting-accounts/{hosting_account}/databases', [HostingAccountController::class, 'createDatabase'])
    ->name('hosting-accounts.databases.store');
Route::delete('hosting-accounts/{hosting_account}/databases/{database}', [HostingAccountController::class, 'deleteDatabase'])
    ->name('hosting-accounts.databases.destroy');
Route::get('hosting-accounts/{hosting_account}/databases/{database}/phpmyadmin', [HostingAccountController::class, 'phpMyAdminSso'])
    ->name('hosting-accounts.databases.phpmyadmin');
Route::post('hosting-accounts/{hosting_account}/ssl', [HostingAccountController::class, 'issueSsl'])
    ->name('hosting-accounts.ssl.store');
Route::post('hosting-accounts/{hosting_account}/php-version', [HostingAccountController::class, 'changePhpVersion'])
    ->name('hosting-accounts.php-version.update');
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

Route::get('hosting-accounts/{hosting_account}/files', [FileManagerController::class, 'index'])
    ->name('hosting-accounts.files.index');
Route::get('hosting-accounts/{hosting_account}/files/edit', [FileManagerController::class, 'edit'])
    ->name('hosting-accounts.files.edit');
Route::post('hosting-accounts/{hosting_account}/files/edit', [FileManagerController::class, 'update'])
    ->name('hosting-accounts.files.update');
Route::post('hosting-accounts/{hosting_account}/files/directories', [FileManagerController::class, 'storeDirectory'])
    ->name('hosting-accounts.files.directories.store');
Route::post('hosting-accounts/{hosting_account}/files/new', [FileManagerController::class, 'storeFile'])
    ->name('hosting-accounts.files.store');
Route::delete('hosting-accounts/{hosting_account}/files', [FileManagerController::class, 'destroy'])
    ->name('hosting-accounts.files.destroy');
Route::post('hosting-accounts/{hosting_account}/files/rename', [FileManagerController::class, 'rename'])
    ->name('hosting-accounts.files.rename');

Route::get('hosting-accounts/{hosting_account}/cron', [CronJobController::class, 'index'])
    ->name('hosting-accounts.cron.index');
Route::post('hosting-accounts/{hosting_account}/cron', [CronJobController::class, 'store'])
    ->name('hosting-accounts.cron.store');
Route::delete('hosting-accounts/{hosting_account}/cron/{cron_job}', [CronJobController::class, 'destroy'])
    ->name('hosting-accounts.cron.destroy');

Route::get('hosting-accounts/{hosting_account}/ssh', [SshAccessController::class, 'index'])
    ->name('hosting-accounts.ssh.index');
Route::post('hosting-accounts/{hosting_account}/ssh/toggle', [SshAccessController::class, 'toggle'])
    ->name('hosting-accounts.ssh.toggle');
Route::post('hosting-accounts/{hosting_account}/ssh/password', [SshAccessController::class, 'regeneratePassword'])
    ->name('hosting-accounts.ssh.password.regenerate');
Route::post('hosting-accounts/{hosting_account}/ssh/password/custom', [SshAccessController::class, 'updatePassword'])
    ->name('hosting-accounts.ssh.password.update');
Route::post('hosting-accounts/{hosting_account}/ssh/keys', [SshAccessController::class, 'storeKey'])
    ->name('hosting-accounts.ssh.keys.store');
Route::delete('hosting-accounts/{hosting_account}/ssh/keys/{ssh_key}', [SshAccessController::class, 'destroyKey'])
    ->name('hosting-accounts.ssh.keys.destroy');

Route::get('hosting-accounts/{hosting_account}/dns', [DnsZoneController::class, 'index'])
    ->name('hosting-accounts.dns.index');
Route::post('hosting-accounts/{hosting_account}/dns', [DnsZoneController::class, 'store'])
    ->name('hosting-accounts.dns.store');
Route::get('hosting-accounts/{hosting_account}/dns/{dns_zone}', [DnsZoneController::class, 'show'])
    ->name('hosting-accounts.dns.show');
Route::delete('hosting-accounts/{hosting_account}/dns/{dns_zone}', [DnsZoneController::class, 'destroy'])
    ->name('hosting-accounts.dns.destroy');
Route::post('hosting-accounts/{hosting_account}/dns/{dns_zone}/records', [DnsZoneController::class, 'storeRecord'])
    ->name('hosting-accounts.dns.records.store');
Route::put('hosting-accounts/{hosting_account}/dns/{dns_zone}/records/{dns_record}', [DnsZoneController::class, 'updateRecord'])
    ->name('hosting-accounts.dns.records.update');
Route::delete('hosting-accounts/{hosting_account}/dns/{dns_zone}/records/{dns_record}', [DnsZoneController::class, 'destroyRecord'])
    ->name('hosting-accounts.dns.records.destroy');

Route::get('hosting-accounts/{hosting_account}/mail', [MailDomainController::class, 'index'])
    ->name('hosting-accounts.mail.index');
Route::post('hosting-accounts/{hosting_account}/mail', [MailDomainController::class, 'store'])
    ->name('hosting-accounts.mail.store');
Route::get('hosting-accounts/{hosting_account}/mail/{mail_domain}', [MailDomainController::class, 'show'])
    ->name('hosting-accounts.mail.show');
Route::delete('hosting-accounts/{hosting_account}/mail/{mail_domain}', [MailDomainController::class, 'destroy'])
    ->name('hosting-accounts.mail.destroy');
Route::post('hosting-accounts/{hosting_account}/mail/{mail_domain}/dns-records', [MailDomainController::class, 'createDnsRecords'])
    ->name('hosting-accounts.mail.dns-records.store');
Route::post('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes', [MailDomainController::class, 'storeMailbox'])
    ->name('hosting-accounts.mail.mailboxes.store');
Route::put('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}/password', [MailDomainController::class, 'updateMailboxPassword'])
    ->name('hosting-accounts.mail.mailboxes.password.update');
Route::delete('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}', [MailDomainController::class, 'destroyMailbox'])
    ->name('hosting-accounts.mail.mailboxes.destroy');
Route::get('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}/webmail', [MailDomainController::class, 'webmailSso'])
    ->name('hosting-accounts.mail.mailboxes.webmail');
