<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * NÃO usa Model::observe() de propósito: observe() só guarda o NOME da
 * classe do observer e resolve ele do zero via container a cada evento
 * disparado — qualquer argumento de construtor (a allowlist por model,
 * que é o ponto central deste design) se perderia. Closures registradas
 * direto via Model::created()/updated()/deleted() não sofrem disso (são
 * guardadas por referência, não re-resolvidas), por isso o registro é
 * feito por esse método estático em vez do padrão Observer tradicional.
 */
class AuditLogObserver
{
    public static function register(string $modelClass, array $auditableFields, array $sensitiveFields = []): void
    {
        $modelClass::created(fn (Model $model) => static::log('created', $model));

        $modelClass::updated(function (Model $model) use ($auditableFields, $sensitiveFields) {
            $changes = array_intersect_key($model->getChanges(), array_flip($auditableFields));

            if ($changes === []) {
                return;
            }

            $formatted = [];

            foreach ($changes as $field => $newValue) {
                $formatted[$field] = in_array($field, $sensitiveFields, true)
                    ? ['[alterado]', '[alterado]']
                    : [$model->getOriginal($field), $newValue];
            }

            static::log('updated', $model, $formatted);
        });

        $modelClass::deleted(fn (Model $model) => static::log('deleted', $model));
    }

    private static function log(string $action, Model $model, ?array $changes = null): void
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'subject_type' => class_basename($model),
            'subject_id' => $model->getKey(),
            'subject_label' => static::resolveLabel($model),
            'changes' => $changes,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    private static function resolveLabel(Model $model): string
    {
        foreach (['primary_domain', 'domain', 'name', 'hostname'] as $attribute) {
            if (! empty($model->{$attribute})) {
                return (string) $model->{$attribute};
            }
        }

        return '#'.$model->getKey();
    }
}
