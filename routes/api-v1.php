<?php

use App\Domain\Api\Http\Controllers\V1\AppController;
use App\Domain\Api\Http\Controllers\V1\BackupController;
use App\Domain\Api\Http\Controllers\V1\CronJobController;
use App\Domain\Api\Http\Controllers\V1\DatabaseController;
use App\Domain\Api\Http\Controllers\V1\DnsController;
use App\Domain\Api\Http\Controllers\V1\DomainController;
use App\Domain\Api\Http\Controllers\V1\FolderProtectionController;
use App\Domain\Api\Http\Controllers\V1\FtpAccountController;
use App\Domain\Api\Http\Controllers\V1\HostingAccountController;
use App\Domain\Api\Http\Controllers\V1\HotlinkProtectionController;
use App\Domain\Api\Http\Controllers\V1\MailController;
use App\Domain\Api\Http\Controllers\V1\MalwareScanController;
use App\Domain\Api\Http\Controllers\V1\MimeTypeRuleController;
use App\Domain\Api\Http\Controllers\V1\PlanController;
use App\Domain\Api\Http\Controllers\V1\ResourceUsageController;
use App\Domain\Api\Http\Controllers\V1\ServerController;
use App\Domain\Api\Http\Controllers\V1\SiteRedirectController;
use App\Domain\Api\Http\Controllers\V1\SshKeyController;
use App\Domain\Api\Http\Controllers\V1\TicketController;
use Illuminate\Support\Facades\Route;

// Machine-to-machine: sistema externo (ex: outro painel) -> Painel,
// autenticado por token Sanctum (ver Admin > Integrações de API). Todo
// token tem acesso completo — sem escopo por conta nessa v1.
// "throttle:60,1" (nativo do Laravel, sem limiter customizado) chaveia
// automaticamente pelo ApiClient autenticado, nunca por IP, já que
// "auth:sanctum" roda antes em todo request deste grupo.
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('servers', [ServerController::class, 'index'])->name('servers.index');

    Route::get('hosting-accounts', [HostingAccountController::class, 'index'])->name('hosting-accounts.index');
    Route::post('hosting-accounts', [HostingAccountController::class, 'store'])->name('hosting-accounts.store');
    Route::get('hosting-accounts/{hosting_account}', [HostingAccountController::class, 'show'])->name('hosting-accounts.show');
    Route::post('hosting-accounts/{hosting_account}/suspend', [HostingAccountController::class, 'suspend'])->name('hosting-accounts.suspend');
    Route::post('hosting-accounts/{hosting_account}/reactivate', [HostingAccountController::class, 'reactivate'])->name('hosting-accounts.reactivate');
    Route::delete('hosting-accounts/{hosting_account}', [HostingAccountController::class, 'destroy'])->name('hosting-accounts.destroy');

    // Sub-recursos de leitura — todos GET, escopados por conta.
    Route::get('hosting-accounts/{hosting_account}/domains', [DomainController::class, 'index'])->name('hosting-accounts.domains.index');
    Route::get('hosting-accounts/{hosting_account}/databases', [DatabaseController::class, 'index'])->name('hosting-accounts.databases.index');
    Route::get('hosting-accounts/{hosting_account}/backups', [BackupController::class, 'index'])->name('hosting-accounts.backups.index');
    Route::get('hosting-accounts/{hosting_account}/cron-jobs', [CronJobController::class, 'index'])->name('hosting-accounts.cron-jobs.index');
    Route::get('hosting-accounts/{hosting_account}/ftp-accounts', [FtpAccountController::class, 'index'])->name('hosting-accounts.ftp-accounts.index');
    Route::get('hosting-accounts/{hosting_account}/mail', [MailController::class, 'index'])->name('hosting-accounts.mail.index');
    Route::get('hosting-accounts/{hosting_account}/malware-scans', [MalwareScanController::class, 'index'])->name('hosting-accounts.malware-scans.index');
    Route::get('hosting-accounts/{hosting_account}/apps', [AppController::class, 'index'])->name('hosting-accounts.apps.index');
    Route::get('hosting-accounts/{hosting_account}/tickets', [TicketController::class, 'index'])->name('hosting-accounts.tickets.index');
    Route::get('hosting-accounts/{hosting_account}/dns', [DnsController::class, 'index'])->name('hosting-accounts.dns.index');
    Route::get('hosting-accounts/{hosting_account}/folder-protections', [FolderProtectionController::class, 'index'])->name('hosting-accounts.folder-protections.index');
    Route::get('hosting-accounts/{hosting_account}/site-redirects', [SiteRedirectController::class, 'index'])->name('hosting-accounts.site-redirects.index');
    Route::get('hosting-accounts/{hosting_account}/hotlink-protection', [HotlinkProtectionController::class, 'index'])->name('hosting-accounts.hotlink-protection.index');
    Route::get('hosting-accounts/{hosting_account}/mime-type-rules', [MimeTypeRuleController::class, 'index'])->name('hosting-accounts.mime-type-rules.index');
    Route::get('hosting-accounts/{hosting_account}/ssh-keys', [SshKeyController::class, 'index'])->name('hosting-accounts.ssh-keys.index');
    Route::get('hosting-accounts/{hosting_account}/resources', [ResourceUsageController::class, 'index'])->name('hosting-accounts.resources.index');
});
