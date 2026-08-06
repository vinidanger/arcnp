<?php

namespace App\Providers;

use App\Domain\Api\Models\ApiClient;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\Plan;
use App\Domain\Hosting\Policies\HostingAccountPolicy;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Policies\ServerPolicy;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Policies\TicketPolicy;
use App\Models\User;
use App\Observers\AuditLogObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Módulos ficam em App\Domain\*, fora da convenção padrão
        // App\Models -> App\Policies do Laravel, então cada Policy
        // de módulo é registrada explicitamente aqui.
        Gate::policy(Server::class, ServerPolicy::class);
        Gate::policy(HostingAccount::class, HostingAccountPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);

        // Log de auditoria — registro via closures (ver
        // App\Observers\AuditLogObserver::register(); NÃO usa
        // Model::observe(), que perderia a allowlist por model — ver
        // comentário na própria classe). Allowlist evita que mutação
        // rotineira (heartbeat, disk_usage_mb, ssl_expires_at,
        // last_used_at de token) vire ruído no log.
        AuditLogObserver::register(
            HostingAccount::class,
            auditableFields: ['status', 'plan_id', 'server_id', 'ssh_enabled', 'waf_enabled', 'primary_domain', 'php_version', 'ssh_password', 'db_master_password'],
            sensitiveFields: ['ssh_password', 'db_master_password'],
        );
        AuditLogObserver::register(
            Server::class,
            auditableFields: ['name', 'hostname', 'ip_address', 'public_ip_address', 'agent_port', 'mysql_service_name', 'mail_hostname', 'ftp_hostname'],
        );
        AuditLogObserver::register(ApiClient::class, auditableFields: ['name']);
        AuditLogObserver::register(
            User::class,
            auditableFields: ['name', 'email', 'type', 'status', 'password'],
            sensitiveFields: ['password'],
        );
        AuditLogObserver::register(
            Plan::class,
            auditableFields: [
                'name', 'disk_quota_mb', 'bandwidth_quota_mb', 'max_databases', 'max_addon_domains',
                'max_cron_jobs', 'max_email_accounts', 'max_backups', 'cpu_cores', 'max_processes',
                'memory_limit_mb', 'io_weight', 'max_db_connections', 'max_db_queries_per_hour', 'is_active',
            ],
        );

        Paginator::useBootstrapFive();
    }
}
