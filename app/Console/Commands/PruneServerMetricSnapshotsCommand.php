<?php

namespace App\Console\Commands;

use App\Domain\Servers\Models\ServerMetricSnapshot;
use Illuminate\Console\Command;

class PruneServerMetricSnapshotsCommand extends Command
{
    protected $signature = 'server-metrics:prune';

    protected $description = 'Apaga snapshots de métrica de servidor com mais de 30 dias — roda diariamente.';

    private const RETENTION_DAYS = 30;

    public function handle(): int
    {
        $deleted = ServerMetricSnapshot::where('recorded_at', '<', now()->subDays(self::RETENTION_DAYS))->delete();

        $this->info("{$deleted} snapshot(s) removido(s).");

        return self::SUCCESS;
    }
}
