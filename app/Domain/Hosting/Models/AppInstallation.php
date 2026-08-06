<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppInstallation extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'path',
        'catalog_slug',
        'status',
        'database_id',
        'admin_username',
        'error',
        'installed_at',
        'detected_version',
        'latest_known_version',
        'version_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
            'version_checked_at' => 'datetime',
        ];
    }

    public function isOutdated(): bool
    {
        return $this->detected_version !== null
            && $this->latest_known_version !== null
            && version_compare($this->detected_version, $this->latest_known_version, '<');
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(HostingDatabase::class, 'database_id');
    }

    public function catalogEntry(): array
    {
        return config('app_catalog.'.$this->catalog_slug, []);
    }

    /**
     * Sempre calculada ao vivo (nunca guardada) — o status de SSL do
     * domínio pode mudar depois da instalação (ex.: emitido mais
     * tarde), então uma URL congelada no banco ficaria errada. Precisa
     * bater com o --url que foi passado pro wp-cli na instalação (ver
     * InstallWordPressAction no Agent) — mudar de HTTP pra HTTPS depois
     * exige atualizar siteurl/home no próprio WordPress, não é só um
     * link diferente aqui.
     */
    public function siteUrl(): ?string
    {
        $account = $this->hostingAccount;

        if (! $account) {
            return null;
        }

        if ($this->domain === $account->primary_domain) {
            $sslActive = $account->ssl_status === 'active';
        } else {
            $sslActive = $account->domains()->where('domain', $this->domain)->value('ssl_status') === 'active';
        }

        $scheme = $sslActive ? 'https' : 'http';
        $path = $this->path ? '/'.$this->path : '';

        return "{$scheme}://{$this->domain}{$path}";
    }
}
