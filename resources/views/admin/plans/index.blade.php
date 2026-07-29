<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Planos') }}</h1>
            <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm">{{ __('Novo plano') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('Disco (MB)') }}</th>
                        <th>{{ __('Banda (MB)') }}</th>
                        <th>{{ __('Máx. bancos') }}</th>
                        <th>{{ __('Ativo') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td>{{ $plan->name }}</td>
                            <td>{{ $plan->disk_quota_mb }}</td>
                            <td>{{ $plan->bandwidth_quota_mb ?? '—' }}</td>
                            <td>{{ $plan->max_databases }}</td>
                            <td>
                                <span class="badge text-bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                    {{ $plan->is_active ? 'sim' : 'não' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</a>
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('Remover este plano?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">{{ __('Nenhum plano cadastrado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
