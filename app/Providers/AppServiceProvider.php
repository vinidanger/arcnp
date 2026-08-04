<?php

namespace App\Providers;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Policies\HostingAccountPolicy;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Policies\ServerPolicy;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Policies\TicketPolicy;
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

        Paginator::useBootstrapFive();
    }
}
