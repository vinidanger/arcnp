<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Mensagens do sistema') }}</h1>
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary btn-sm">{{ __('Nova mensagem') }}</a>
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
                        <th>{{ __('Título') }}</th>
                        <th>{{ __('Audiência') }}</th>
                        <th>{{ __('Início') }}</th>
                        <th>{{ __('Fim') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr>
                            <td>{{ $announcement->title }}</td>
                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ match ($announcement->audience) {
                                        'admin' => __('Admins'),
                                        'client' => __('Clientes'),
                                        default => __('Todos'),
                                    } }}
                                </span>
                            </td>
                            <td>{{ $announcement->starts_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $announcement->ends_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</a>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="d-inline" onsubmit="return confirm('{{ __('Remover essa mensagem?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">{{ __('Nenhuma mensagem cadastrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
