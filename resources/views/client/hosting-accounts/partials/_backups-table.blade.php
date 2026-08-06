@php
    $backupCount = $account->backups->whereIn('status', ['pending', 'completed'])->count();
    $backupLimitReached = $backupCount >= $account->plan->max_backups;
@endphp

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
                <h2 class="h6 mb-0">{{ __('Backups') }}</h2>
                <span class="badge text-bg-{{ $backupLimitReached ? 'warning' : 'secondary' }} rounded-pill">{{ $backupCount }} / {{ $account->plan->max_backups }}</span>
            </div>
            @if ($account->status === 'active')
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('client.hosting-accounts.backup-frequency.update', $account) }}" class="d-flex gap-1">
                        @csrf
                        <select name="backup_frequency" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach (config('hosting.backup_frequencies') as $frequency)
                                <option value="{{ $frequency }}" @selected($account->backup_frequency === $frequency)>
                                    {{ match ($frequency) { 'daily' => __('Automático diário'), 'weekly' => __('Automático semanal'), default => __('Desativado') } }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <form method="POST" action="{{ route('client.hosting-accounts.backups.store', $account) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary" @disabled($backupLimitReached) title="{{ $backupLimitReached ? __('Limite de backups do plano atingido') : '' }}">{{ __('Criar backup agora') }}</button>
                    </form>
                </div>
            @endif
        </div>

        @if ($account->backups->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Data') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Tamanho') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($account->backups as $backup)
                            @php
                                $backupBadge = match ($backup->status) {
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    default => 'info',
                                };
                                $hasFilesArchive = collect($backup->files)->contains(fn ($f) => str_starts_with($f['filename'], 'files-'));
                                $hasDatabaseArchives = collect($backup->files)->contains(fn ($f) => str_starts_with($f['filename'], 'db-'));
                                $totalSize = collect($backup->files)->sum('size');
                            @endphp
                            <tr>
                                <td>{{ $backup->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $backupBadge }}">{{ status_label($backup->status) }}</span>
                                    @if ($backup->status === 'failed' && $backup->error)
                                        <div class="small text-danger">{{ $backup->error }}</div>
                                    @endif
                                </td>
                                <td class="small text-secondary">{{ $backup->status === 'completed' ? number_format($totalSize / 1048576, 1).' MB' : '—' }}</td>
                                <td class="text-end">
                                    @if ($backup->status === 'completed')
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" data-bs-strategy="fixed">
                                                <i class="bi bi-download"></i> {{ __('Baixar') }}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if ($hasFilesArchive)
                                                    <li><a class="dropdown-item" href="{{ route('client.hosting-accounts.backups.bundle', [$account, $backup, 'files']) }}">{{ __('Arquivos') }}</a></li>
                                                @endif
                                                @if ($hasDatabaseArchives)
                                                    <li><a class="dropdown-item" href="{{ route('client.hosting-accounts.backups.bundle', [$account, $backup, 'databases']) }}">{{ __('Bancos de dados') }}</a></li>
                                                @endif
                                                <li><a class="dropdown-item" href="{{ route('client.hosting-accounts.backups.bundle', [$account, $backup, 'all']) }}">{{ __('Completo') }}</a></li>
                                            </ul>
                                        </div>
                                    @endif
                                    <form method="POST" action="{{ route('client.hosting-accounts.backups.destroy', [$account, $backup]) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('Remove esse backup e seus arquivos do servidor. Continuar?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Remover') }}"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="small text-secondary mb-0">{{ __('Nenhum backup ainda.') }}</p>
        @endif
    </div>
</div>
