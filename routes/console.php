<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('servers:mark-stale-offline')->everyMinute();
Schedule::command('backups:run-scheduled')->hourly();
Schedule::command('disk-usage:refresh')->hourly();
Schedule::command('ssl:renew')->daily();
Schedule::command('server-metrics:prune')->daily();
Schedule::command('security:scan-accounts')->daily();
Schedule::command('security:check-cms-versions')->daily();
Schedule::command('uptime:check')->everyFiveMinutes();
