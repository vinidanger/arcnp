<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Estatísticas de tráfego') }} — {{ $domain }}</h1>
    </x-slot>

    <p class="small text-secondary">
        {{ __('Contagem própria, sem depender do Google Analytics — a partir do log do nginx desse domínio. Atualiza 1x por dia.') }}
    </p>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('client.hosting-accounts.traffic.index', $account) }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                    <select id="domain" name="domain" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ($domains as $d)
                            <option value="{{ $d }}" @selected($domain === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if ($stats->isEmpty())
        <div class="card">
            <div class="card-body">
                <p class="small text-secondary mb-0">{{ __('Ainda sem dado coletado pra esse domínio — a primeira coleta roda até o fim do dia.') }}</p>
            </div>
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ __('Últimos 30 dias') }}</h2>
                <canvas id="traffic-chart" data-stats="{{ $stats->toJson() }}" height="80"></canvas>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="stat-tile">
                    <div class="stat-tile-value">{{ number_format($latest->hits) }}</div>
                    <div class="small text-secondary">{{ __('Hits — :date', ['date' => $latest->date->format('d/m')]) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-tile">
                    <div class="stat-tile-value">{{ number_format($latest->unique_visitors) }}</div>
                    <div class="small text-secondary">{{ __('Visitantes únicos — :date', ['date' => $latest->date->format('d/m')]) }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ __('Páginas mais vistas') }} ({{ $latest->date->format('d/m/Y') }})</h2>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Caminho') }}</th>
                            <th class="text-end">{{ __('Hits') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latest->top_paths ?? [] as $entry)
                            <tr>
                                <td><code class="small">{{ $entry['path'] }}</code></td>
                                <td class="text-end">{{ $entry['hits'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-secondary py-3">{{ __('Sem dado.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @vite('resources/js/traffic-chart.js')
    @endif
</x-client-layout>
