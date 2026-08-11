<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Otimização de imagens') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <p class="small text-secondary mb-0">
                {{ __('Gera versões .webp/.avif de cada imagem .jpg/.jpeg/.png da conta — o nginx passa a servir a versão otimizada pra quem o navegador suportar, automaticamente. Pode demorar alguns minutos numa conta grande.') }}
            </p>
            <form method="POST" action="{{ route('client.hosting-accounts.image-optimization.store', $account) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary text-nowrap ms-3">{{ __('Otimizar imagens agora') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Data') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Processadas') }}</th>
                        <th>{{ __('Convertidas') }}</th>
                        <th>{{ __('Puladas') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->imageOptimizations as $optimization)
                        @php
                            $optimizationBadge = match ($optimization->status) {
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'info',
                            };
                        @endphp
                        <tr>
                            <td>{{ $optimization->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge text-bg-{{ $optimizationBadge }}">{{ status_label($optimization->status) }}</span>
                                @if ($optimization->status === 'failed' && $optimization->error)
                                    <div class="small text-danger">{{ $optimization->error }}</div>
                                @endif
                            </td>
                            <td>{{ $optimization->processed_count ?? '—' }}</td>
                            <td>{{ $optimization->converted_count ?? '—' }}</td>
                            <td>{{ $optimization->skipped_count ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">{{ __('Nenhuma otimização ainda.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>
