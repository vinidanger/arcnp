<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Segurança') }} — {{ $server->name }}</h1>
            <a href="{{ route('admin.servers.show', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Voltar') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-info small">
        {{ __('IPs banidos automaticamente pelo fail2ban (tentativas repetidas de login errado via SSH/FTP). Lista sempre ao vivo, não fica guardada aqui — atualize a página pra ver o estado atual.') }}
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Jail') }}</th>
                        <th>{{ __('IP banido') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($fetchError)
                        <tr>
                            <td colspan="3" class="text-center text-danger py-4">{{ $fetchError }}</td>
                        </tr>
                    @elseif (empty($banned))
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhum IP banido no momento.') }}</td>
                        </tr>
                    @else
                        @foreach ($banned as $entry)
                            <tr>
                                <td><code>{{ $entry['jail'] }}</code></td>
                                <td><code>{{ $entry['ip'] }}</code></td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.servers.security.unban', $server) }}">
                                        @csrf
                                        <input type="hidden" name="jail" value="{{ $entry['jail'] }}">
                                        <input type="hidden" name="ip" value="{{ $entry['ip'] }}">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Desbanir') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
