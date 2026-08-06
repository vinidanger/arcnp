<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 mb-0">{{ __('Bancos de dados') }}</h2>
            @if ($account->status === 'active')
                <div class="d-flex gap-2">
                    @if ($account->databases->isNotEmpty())
                        <a href="{{ route('client.hosting-accounts.databases.phpmyadmin-all', $account) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                            <img src="{{ asset('storage/images/icons/phpmyadmin.png') }}" alt="" class="icon-img-sm"> {{ __('phpMyAdmin (todos os bancos)') }}
                        </a>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#add-database-modal">
                        <i class="bi bi-plus-lg"></i> {{ __('Criar banco de dados') }}
                    </button>
                </div>
            @endif
        </div>

        @if ($account->databases->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Banco') }}</th>
                            <th>{{ __('Usuário') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($account->databases as $database)
                            <tr>
                                <td><code>{{ $database->db_name }}</code></td>
                                <td><code>{{ $database->db_username }}</code></td>
                                <td class="text-end">
                                    <a href="{{ route('client.hosting-accounts.databases.phpmyadmin', [$account, $database]) }}"
                                       class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{{ __('phpMyAdmin') }}</a>
                                    <form method="POST" action="{{ route('client.hosting-accounts.databases.destroy', [$account, $database]) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ __('Remove o banco e o usuário MySQL. Continuar?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @unless ($account->status === 'active')
            <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
        @endunless
    </div>
</div>
