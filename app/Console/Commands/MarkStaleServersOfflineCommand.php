<?php

namespace App\Console\Commands;

use App\Domain\Servers\Models\Server;
use Illuminate\Console\Command;

class MarkStaleServersOfflineCommand extends Command
{
    protected $signature = 'servers:mark-stale-offline';

    protected $description = 'Marca offline servidores cujo heartbeat atrasou (timer roda a cada 60s no Agent).';

    /**
     * Tolera até 2 heartbeats perdidos antes de marcar offline. Servidores
     * "pending" (nunca pareados) ficam de fora — nunca estiveram online.
     */
    private const STALE_AFTER_SECONDS = 180;

    public function handle(): int
    {
        $count = Server::where('agent_status', 'online')
            ->where(function ($query) {
                $query->whereNull('last_heartbeat_at')
                    ->orWhere('last_heartbeat_at', '<', now()->subSeconds(self::STALE_AFTER_SECONDS));
            })
            ->update(['agent_status' => 'offline']);

        if ($count > 0) {
            $this->info("{$count} servidor(es) marcado(s) como offline.");
        }

        return self::SUCCESS;
    }
}
