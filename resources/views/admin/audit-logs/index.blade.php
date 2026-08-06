<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Log de auditoria') }}</h1>
    </x-slot>

    <p class="text-secondary small">
        {{ __('Ações administrativas sensíveis (contas, servidores, credenciais de API, usuários, planos). Alterações rotineiras feitas pelo próprio sistema (uso de disco, renovação de SSL, heartbeat) não aparecem aqui de propósito.') }}
    </p>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="subject_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('Todos os tipos') }}</option>
                        @foreach ($subjectTypes as $type)
                            <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" title="{{ __('De') }}">
                </div>
                <div class="col-auto">
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" title="{{ __('Até') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Filtrar') }}</button>
                    @if (request('subject_type') || request('from') || request('to'))
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-link">{{ __('Limpar') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Quando') }}</th>
                        <th>{{ __('Quem') }}</th>
                        <th>{{ __('Ação') }}</th>
                        <th>{{ __('Sobre') }}</th>
                        <th>{{ __('Mudanças') }}</th>
                        <th>{{ __('IP') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user_name ?? __('Sistema') }}</td>
                            <td>
                                <span class="badge text-bg-{{ match ($log->action) { 'created' => 'success', 'deleted' => 'danger', default => 'secondary' } }}">
                                    {{ __($log->action) }}
                                </span>
                            </td>
                            <td>{{ $log->subject_type }}: {{ $log->subject_label }}</td>
                            <td class="small">
                                @if ($log->changes)
                                    @foreach ($log->changes as $field => [$from, $to])
                                        <div><code>{{ $field }}</code>: {{ $from ?? __('vazio') }} → {{ $to ?? __('vazio') }}</div>
                                    @endforeach
                                @endif
                            </td>
                            <td class="small text-secondary">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                {{ __('Nenhum registro ainda.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</x-admin-layout>
