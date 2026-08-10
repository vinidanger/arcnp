<?php

use App\Domain\Api\Http\Controllers\Admin\ApiClientController;
use App\Domain\Clients\Http\Controllers\Admin\ClientController;
use App\Domain\Hosting\Http\Controllers\Admin\CronJobController;
use App\Domain\Hosting\Http\Controllers\Admin\DnsZoneController;
use App\Domain\Hosting\Http\Controllers\Admin\DomainLogController;
use App\Domain\Hosting\Http\Controllers\Admin\TrafficStatsController;
use App\Domain\Hosting\Http\Controllers\Admin\FileManagerController;
use App\Domain\Hosting\Http\Controllers\Admin\FolderProtectionController;
use App\Domain\Hosting\Http\Controllers\Admin\AppInstallerController;
use App\Domain\Hosting\Http\Controllers\Admin\MalwareScanController;
use App\Domain\Hosting\Http\Controllers\Admin\FtpAccountController;
use App\Domain\Hosting\Http\Controllers\Admin\HostedAppController;
use App\Domain\Hosting\Http\Controllers\Admin\HotlinkProtectionController;
use App\Domain\Hosting\Http\Controllers\Admin\MimeTypeController;
use App\Domain\Hosting\Http\Controllers\Admin\MailDomainController;
use App\Domain\Hosting\Http\Controllers\Admin\MailLogController;
use App\Domain\Hosting\Http\Controllers\Admin\HostingAccountController;
use App\Domain\Hosting\Http\Controllers\Admin\PhpSettingsController;
use App\Domain\Hosting\Http\Controllers\Admin\ResourceLimitsController;
use App\Domain\Hosting\Http\Controllers\Admin\PlanController;
use App\Domain\Hosting\Http\Controllers\Admin\SiteRedirectController;
use App\Domain\Hosting\Http\Controllers\Admin\SshAccessController;
use App\Domain\Servers\Http\Controllers\Admin\PhpExtensionController;
use App\Domain\Servers\Http\Controllers\Admin\SecurityController;
use App\Domain\Servers\Http\Controllers\Admin\ServerController;
use App\Domain\Support\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::resource('clients', ClientController::class)->except(['show', 'destroy']);

Route::resource('announcements', AnnouncementController::class)->except(['show']);

Route::resource('api-clients', ApiClientController::class)->only(['index', 'create', 'store', 'destroy']);
Route::get('api-clients-docs', [ApiClientController::class, 'docs'])->name('api-clients.docs');

Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

Route::resource('servers', ServerController::class);
Route::post('servers/{server}/regenerate-credential', [ServerController::class, 'regenerateCredential'])
    ->name('servers.regenerate-credential');
Route::post('servers/{server}/test-connection', [ServerController::class, 'testConnection'])
    ->name('servers.test-connection');
Route::post('servers/{server}/collect-info', [ServerController::class, 'collectInfo'])
    ->name('servers.collect-info');
Route::get('servers/{server}/php-extensions', [PhpExtensionController::class, 'index'])
    ->name('servers.php-extensions.index');
Route::post('servers/{server}/php-extensions/toggle', [PhpExtensionController::class, 'toggle'])
    ->name('servers.php-extensions.toggle');
Route::get('servers/{server}/security', [SecurityController::class, 'index'])
    ->name('servers.security.index');
Route::post('servers/{server}/security/unban', [SecurityController::class, 'unban'])
    ->name('servers.security.unban');

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
Route::get('hosting-accounts/{hosting_account}/databases/phpmyadmin', [HostingAccountController::class, 'phpMyAdminSsoAll'])
    ->name('hosting-accounts.databases.phpmyadmin-all');
Route::post('hosting-accounts/{hosting_account}/ssl', [HostingAccountController::class, 'issueSsl'])
    ->name('hosting-accounts.ssl.store');
Route::post('hosting-accounts/{hosting_account}/waf', [HostingAccountController::class, 'updateWaf'])
    ->name('hosting-accounts.waf.update');
Route::post('hosting-accounts/{hosting_account}/domains/{domain}/waf', [HostingAccountController::class, 'updateDomainWaf'])
    ->name('hosting-accounts.domains.waf.update');
Route::post('hosting-accounts/{hosting_account}/domains', [HostingAccountController::class, 'storeDomain'])
    ->name('hosting-accounts.domains.store');
Route::delete('hosting-accounts/{hosting_account}/domains/{domain}', [HostingAccountController::class, 'destroyDomain'])
    ->name('hosting-accounts.domains.destroy');
Route::post('hosting-accounts/{hosting_account}/domains/staging-clone', [HostingAccountController::class, 'storeStagingClone'])
    ->name('hosting-accounts.domains.staging-clone.store');
Route::patch('hosting-accounts/{hosting_account}/public-path', [HostingAccountController::class, 'updatePublicPath'])
    ->name('hosting-accounts.public-path.update');
Route::patch('hosting-accounts/{hosting_account}/domains/{domain}/public-path', [HostingAccountController::class, 'updateDomainPublicPath'])
    ->name('hosting-accounts.domains.public-path.update');
Route::post('hosting-accounts/{hosting_account}/backup-frequency', [HostingAccountController::class, 'updateBackupFrequency'])
    ->name('hosting-accounts.backup-frequency.update');
Route::post('hosting-accounts/{hosting_account}/backups', [HostingAccountController::class, 'createBackup'])
    ->name('hosting-accounts.backups.store');
Route::delete('hosting-accounts/{hosting_account}/backups/{backup}', [HostingAccountController::class, 'destroyBackup'])
    ->name('hosting-accounts.backups.destroy');
Route::get('hosting-accounts/{hosting_account}/backups/{backup}/bundle/{group}', [HostingAccountController::class, 'downloadBackupBundle'])
    ->name('hosting-accounts.backups.bundle');
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
Route::post('hosting-accounts/{hosting_account}/files/upload', [FileManagerController::class, 'upload'])
    ->name('hosting-accounts.files.upload');
Route::post('hosting-accounts/{hosting_account}/files/compress', [FileManagerController::class, 'compress'])
    ->name('hosting-accounts.files.compress');
Route::post('hosting-accounts/{hosting_account}/files/extract', [FileManagerController::class, 'extract'])
    ->name('hosting-accounts.files.extract');
Route::get('hosting-accounts/{hosting_account}/files/download', [FileManagerController::class, 'download'])
    ->name('hosting-accounts.files.download');

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

Route::get('hosting-accounts/{hosting_account}/php', [PhpSettingsController::class, 'index'])
    ->name('hosting-accounts.php.index');
Route::post('hosting-accounts/{hosting_account}/php/version', [PhpSettingsController::class, 'updateVersion'])
    ->name('hosting-accounts.php.version.update');
Route::post('hosting-accounts/{hosting_account}/php/settings', [PhpSettingsController::class, 'updateSettings'])
    ->name('hosting-accounts.php.settings.update');
Route::post('hosting-accounts/{hosting_account}/php/zend-extensions', [PhpSettingsController::class, 'updateZendExtensions'])
    ->name('hosting-accounts.php.zend-extensions.update');

Route::get('hosting-accounts/{hosting_account}/domains/{domain}/php', [PhpSettingsController::class, 'indexForDomain'])
    ->name('hosting-accounts.domains.php.index');
Route::post('hosting-accounts/{hosting_account}/domains/{domain}/php/version', [PhpSettingsController::class, 'updateVersionForDomain'])
    ->name('hosting-accounts.domains.php.version.update');
Route::post('hosting-accounts/{hosting_account}/domains/{domain}/php/settings', [PhpSettingsController::class, 'updateSettingsForDomain'])
    ->name('hosting-accounts.domains.php.settings.update');
Route::post('hosting-accounts/{hosting_account}/domains/{domain}/php/zend-extensions', [PhpSettingsController::class, 'updateZendExtensionsForDomain'])
    ->name('hosting-accounts.domains.php.zend-extensions.update');

Route::get('hosting-accounts/{hosting_account}/resources', [ResourceLimitsController::class, 'index'])
    ->name('hosting-accounts.resources.index');
Route::post('hosting-accounts/{hosting_account}/resources/reapply', [ResourceLimitsController::class, 'reapply'])
    ->name('hosting-accounts.resources.reapply');

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
Route::post('hosting-accounts/{hosting_account}/mail/{mail_domain}/check-health', [MailDomainController::class, 'checkHealth'])
    ->name('hosting-accounts.mail.check-health');
Route::post('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes', [MailDomainController::class, 'storeMailbox'])
    ->name('hosting-accounts.mail.mailboxes.store');
Route::put('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}/password', [MailDomainController::class, 'updateMailboxPassword'])
    ->name('hosting-accounts.mail.mailboxes.password.update');
Route::delete('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}', [MailDomainController::class, 'destroyMailbox'])
    ->name('hosting-accounts.mail.mailboxes.destroy');
Route::get('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}/webmail', [MailDomainController::class, 'webmailSso'])
    ->name('hosting-accounts.mail.mailboxes.webmail');
Route::put('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}/vacation', [MailDomainController::class, 'updateVacation'])
    ->name('hosting-accounts.mail.mailboxes.vacation.update');
Route::post('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}/filters', [MailDomainController::class, 'storeFilter'])
    ->name('hosting-accounts.mail.mailboxes.filters.store');
Route::delete('hosting-accounts/{hosting_account}/mail/{mail_domain}/mailboxes/{mailbox}/filters/{filter}', [MailDomainController::class, 'destroyFilter'])
    ->name('hosting-accounts.mail.mailboxes.filters.destroy');
Route::post('hosting-accounts/{hosting_account}/mail/{mail_domain}/forwarders', [MailDomainController::class, 'storeForwarder'])
    ->name('hosting-accounts.mail.forwarders.store');
Route::delete('hosting-accounts/{hosting_account}/mail/{mail_domain}/forwarders/{forwarder}', [MailDomainController::class, 'destroyForwarder'])
    ->name('hosting-accounts.mail.forwarders.destroy');

Route::get('hosting-accounts/{hosting_account}/protected-folders', [FolderProtectionController::class, 'index'])
    ->name('hosting-accounts.protected-folders.index');
Route::post('hosting-accounts/{hosting_account}/protected-folders', [FolderProtectionController::class, 'store'])
    ->name('hosting-accounts.protected-folders.store');
Route::delete('hosting-accounts/{hosting_account}/protected-folders/{folder_protection}', [FolderProtectionController::class, 'destroy'])
    ->name('hosting-accounts.protected-folders.destroy');

Route::get('hosting-accounts/{hosting_account}/redirects', [SiteRedirectController::class, 'index'])
    ->name('hosting-accounts.redirects.index');
Route::post('hosting-accounts/{hosting_account}/redirects', [SiteRedirectController::class, 'store'])
    ->name('hosting-accounts.redirects.store');
Route::delete('hosting-accounts/{hosting_account}/redirects/{redirect}', [SiteRedirectController::class, 'destroy'])
    ->name('hosting-accounts.redirects.destroy');

Route::get('hosting-accounts/{hosting_account}/hotlink-protection', [HotlinkProtectionController::class, 'index'])
    ->name('hosting-accounts.hotlink-protection.index');
Route::put('hosting-accounts/{hosting_account}/hotlink-protection', [HotlinkProtectionController::class, 'update'])
    ->name('hosting-accounts.hotlink-protection.update');

Route::get('hosting-accounts/{hosting_account}/logs', [DomainLogController::class, 'index'])
    ->name('hosting-accounts.logs.index');
Route::get('hosting-accounts/{hosting_account}/traffic', [TrafficStatsController::class, 'index'])
    ->name('hosting-accounts.traffic.index');
Route::get('hosting-accounts/{hosting_account}/mail-log', [MailLogController::class, 'index'])
    ->name('hosting-accounts.mail-log.index');

Route::get('hosting-accounts/{hosting_account}/ftp', [FtpAccountController::class, 'index'])
    ->name('hosting-accounts.ftp.index');
Route::post('hosting-accounts/{hosting_account}/ftp', [FtpAccountController::class, 'store'])
    ->name('hosting-accounts.ftp.store');
Route::put('hosting-accounts/{hosting_account}/ftp/{ftp_account}/password', [FtpAccountController::class, 'updatePassword'])
    ->name('hosting-accounts.ftp.password.update');
Route::delete('hosting-accounts/{hosting_account}/ftp/{ftp_account}', [FtpAccountController::class, 'destroy'])
    ->name('hosting-accounts.ftp.destroy');

Route::get('hosting-accounts/{hosting_account}/apps', [HostedAppController::class, 'index'])
    ->name('hosting-accounts.apps.index');
Route::post('hosting-accounts/{hosting_account}/apps', [HostedAppController::class, 'store'])
    ->name('hosting-accounts.apps.store');
Route::post('hosting-accounts/{hosting_account}/apps/{app}/restart', [HostedAppController::class, 'restart'])
    ->name('hosting-accounts.apps.restart');
Route::delete('hosting-accounts/{hosting_account}/apps/{app}', [HostedAppController::class, 'destroy'])
    ->name('hosting-accounts.apps.destroy');

Route::get('hosting-accounts/{hosting_account}/installer', [AppInstallerController::class, 'index'])
    ->name('hosting-accounts.installer.index');
Route::post('hosting-accounts/{hosting_account}/installer/wordpress', [AppInstallerController::class, 'storeWordPress'])
    ->name('hosting-accounts.installer.wordpress');
Route::post('hosting-accounts/{hosting_account}/installer/generic-zip', [AppInstallerController::class, 'storeGenericZip'])
    ->name('hosting-accounts.installer.generic-zip');
Route::delete('hosting-accounts/{hosting_account}/installer/{installation}', [AppInstallerController::class, 'destroy'])
    ->name('hosting-accounts.installer.destroy');

Route::get('hosting-accounts/{hosting_account}/malware', [MalwareScanController::class, 'index'])
    ->name('hosting-accounts.malware.index');
Route::post('hosting-accounts/{hosting_account}/malware', [MalwareScanController::class, 'store'])
    ->name('hosting-accounts.malware.store');
Route::post('hosting-accounts/{hosting_account}/malware/quarantine', [MalwareScanController::class, 'quarantine'])
    ->name('hosting-accounts.malware.quarantine');
Route::post('hosting-accounts/{hosting_account}/malware/restore', [MalwareScanController::class, 'restore'])
    ->name('hosting-accounts.malware.restore');
Route::post('hosting-accounts/{hosting_account}/malware/ignore', [MalwareScanController::class, 'ignore'])
    ->name('hosting-accounts.malware.ignore');

Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
Route::post('tickets/{ticket}/messages', [TicketController::class, 'storeMessage'])->name('tickets.messages.store');
Route::post('tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
Route::post('tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');

Route::get('hosting-accounts/{hosting_account}/mime-types', [MimeTypeController::class, 'index'])
    ->name('hosting-accounts.mime-types.index');
Route::post('hosting-accounts/{hosting_account}/mime-types', [MimeTypeController::class, 'store'])
    ->name('hosting-accounts.mime-types.store');
Route::delete('hosting-accounts/{hosting_account}/mime-types/{mime_type_rule}', [MimeTypeController::class, 'destroy'])
    ->name('hosting-accounts.mime-types.destroy');
