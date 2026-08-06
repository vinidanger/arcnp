<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Models\User;
use App\Notifications\SiteAvailabilityChangedNotification;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckUptimeCommand extends Command
{
    protected $signature = 'uptime:check';

    protected $description = 'Checa se cada domínio de conta ativa está respondendo — roda a cada 5 minutos.';

    /** Falhas seguidas antes de alertar — evita alarme por 1 blip passageiro. */
    private const FAILURE_THRESHOLD = 2;

    public function handle(): int
    {
        $accounts = HostingAccount::where('status', 'active')->with('domains')->get();

        $targets = [];

        foreach ($accounts as $account) {
            $targets[] = [
                'model' => $account,
                'account' => $account,
                'url' => ($account->ssl_status === 'active' ? 'https' : 'http')."://{$account->primary_domain}",
                'label' => $account->primary_domain,
            ];

            foreach ($account->domains as $domain) {
                $targets[] = [
                    'model' => $domain,
                    'account' => $account,
                    'url' => ($domain->ssl_status === 'active' ? 'https' : 'http')."://{$domain->domain}",
                    'label' => $domain->domain,
                ];
            }
        }

        if ($targets === []) {
            $this->info('Nenhum alvo pra checar.');

            return self::SUCCESS;
        }

        $responses = Http::pool(fn ($pool) => collect($targets)
            ->map(fn ($target) => $pool->timeout(8)->get($target['url']))
            ->all());

        $checked = 0;

        foreach ($targets as $index => $target) {
            try {
                $this->applyResult($target, $this->isUp($responses[$index] ?? null));
                $checked++;
            } catch (Throwable $e) {
                $this->warn("Falha ao processar checagem de {$target['label']}: {$e->getMessage()}");
            }
        }

        $this->info("{$checked} alvo(s) checado(s).");

        return self::SUCCESS;
    }

    private function isUp(mixed $response): bool
    {
        if ($response instanceof ConnectionException || $response instanceof Throwable) {
            return false;
        }

        return ! $response->serverError();
    }

    /** @param array{model: HostingAccount|Domain, account: HostingAccount, url: string, label: string} $target */
    private function applyResult(array $target, bool $isUp): void
    {
        $model = $target['model'];
        $wasDown = $model->uptime_status === 'down';

        if ($isUp) {
            $model->uptime_consecutive_failures = 0;
            $model->uptime_status = 'up';
            $model->uptime_checked_at = now();

            if ($wasDown) {
                $model->uptime_down_since = null;
                $model->uptime_alert_sent_at = null;
                $model->save();
                $this->notify($target, isDown: false);

                return;
            }

            $model->save();

            return;
        }

        $model->uptime_consecutive_failures++;
        $model->uptime_checked_at = now();

        if ($model->uptime_consecutive_failures >= self::FAILURE_THRESHOLD) {
            $model->uptime_status = 'down';

            if ($model->uptime_down_since === null) {
                $model->uptime_down_since = now();
            }

            $shouldAlert = $model->uptime_alert_sent_at === null;

            if ($shouldAlert) {
                $model->uptime_alert_sent_at = now();
            }

            $model->save();

            if ($shouldAlert) {
                $this->notify($target, isDown: true);
            }

            return;
        }

        $model->save();
    }

    private function notify(array $target, bool $isDown): void
    {
        $account = $target['account'];
        $recipients = User::where('type', 'admin')->get();

        if ($account->client) {
            $recipients->push($account->client);
        }

        Notification::send($recipients, new SiteAvailabilityChangedNotification($target['label'], $isDown));
    }
}
