<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Servidores') }}</h1>
            <a href="{{ route('admin.servers.create') }}" class="btn btn-primary btn-sm">
                {{ __('Novo servidor') }}
            </a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->serverDeletion->isNotEmpty())
        <div class="alert alert-danger">{{ $errors->serverDeletion->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('Todos os status') }}</option>
                        <option value="online" @selected(request('status') === 'online')>{{ __('Online') }}</option>
                        <option value="offline" @selected(request('status') === 'offline')>{{ __('Offline') }}</option>
                    </select>
                </div>
                @if (request('status'))
                    <div class="col-auto">
                        <a href="{{ route('admin.servers.index') }}" class="btn btn-sm btn-link">{{ __('Limpar') }}</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('IP') }}</th>
                        <th>{{ __('SO') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Último heartbeat') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servers as $server)
                        <tr>
                            <td>
                                <a href="{{ route('admin.servers.show', $server) }}">{{ $server->name }}</a>
                            </td>
                            <td>{{ $server->ip_address }}</td>
                            <td>{{ $server->os ?? '—' }}</td>
                            <td>
                                @php
                                    $badge = match ($server->agent_status) {
                                        'online' => 'success',
                                        'offline' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $badge }}">{{ status_label($server->agent_status) }}</span>
                            </td>
                            <td>{{ $server->last_heartbeat_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="openServerDeleteModal('{{ route('admin.servers.destroy', $server) }}', {{ Illuminate\Support\Js::from($server->name) }}, {{ Illuminate\Support\Js::from($server->ip_address) }})">
                                    {{ __('Excluir') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                {{ __('Nenhum servidor cadastrado ainda.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $servers->links() }}
    </div>

    {{-- Modal único, reaproveitado por todas as linhas — a ação/nome/IP
         são preenchidos por JS no clique (openServerDeleteModal), em vez
         de um modal por servidor (evitaria IDs duplicados na página). --}}
    <x-modal name="confirm-server-deletion" focusable>
        <form method="post" id="server-deletion-form" class="p-3">
            @csrf
            @method('delete')

            <h2 class="h5">
                {{ __('Tem certeza que deseja excluir este servidor?') }}
            </h2>

            <p class="small text-secondary">
                {{ __('Isso remove o servidor') }} <strong id="server-deletion-name"></strong>
                ({{ __('IP') }} <span id="server-deletion-ip"></span>) {{ __('e revoga as credenciais de pareamento. Só é possível excluir se não houver nenhuma hospedagem cadastrada nele. Digite sua senha para confirmar.') }}
            </p>

            <div class="mt-3">
                <x-input-label for="server-deletion-password" value="{{ __('Senha') }}" class="visually-hidden" />
                <x-text-input
                    id="server-deletion-password"
                    name="password"
                    type="password"
                    placeholder="{{ __('Senha') }}"
                    class="w-full"
                />
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <x-secondary-button type="button" data-bs-dismiss="modal">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Excluir servidor') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>

    <script>
        function openServerDeleteModal(actionUrl, name, ip) {
            document.getElementById('server-deletion-form').action = actionUrl;
            document.getElementById('server-deletion-name').textContent = name;
            document.getElementById('server-deletion-ip').textContent = ip;
            window.arcnModal.show('confirm-server-deletion');
        }
    </script>
</x-admin-layout>
