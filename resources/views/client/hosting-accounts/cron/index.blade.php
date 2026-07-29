<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Tarefas agendadas (cron)') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Nova tarefa') }}</h2>

            <div class="mb-2" style="max-width: 20rem;">
                <x-input-label for="preset" value="{{ __('Frequência') }}" class="small mb-1" />
                <select id="preset" class="form-select form-select-sm">
                    <option value="">{{ __('Personalizado') }}</option>
                    <option value="* * * * *">{{ __('A cada minuto') }}</option>
                    <option value="*/5 * * * *">{{ __('A cada 5 minutos') }}</option>
                    <option value="*/10 * * * *">{{ __('A cada 10 minutos') }}</option>
                    <option value="*/15 * * * *">{{ __('A cada 15 minutos') }}</option>
                    <option value="*/30 * * * *">{{ __('A cada 30 minutos') }}</option>
                    <option value="0 * * * *">{{ __('De hora em hora') }}</option>
                    <option value="0 0 * * *">{{ __('Todo dia (meia-noite)') }}</option>
                    <option value="0 0 * * 0">{{ __('Toda semana (domingo, meia-noite)') }}</option>
                    <option value="0 0 1 * *">{{ __('Todo mês (dia 1, meia-noite)') }}</option>
                </select>
            </div>

            <form method="POST" action="{{ route('client.hosting-accounts.cron.store', $account) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <x-input-label for="minute" value="{{ __('Minuto') }}" class="small mb-1" />
                    <input type="text" id="minute" name="minute" value="{{ old('minute', '*') }}" class="form-control form-control-sm" style="width: 5rem;" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="hour" value="{{ __('Hora') }}" class="small mb-1" />
                    <input type="text" id="hour" name="hour" value="{{ old('hour', '*') }}" class="form-control form-control-sm" style="width: 5rem;" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="day" value="{{ __('Dia') }}" class="small mb-1" />
                    <input type="text" id="day" name="day" value="{{ old('day', '*') }}" class="form-control form-control-sm" style="width: 5rem;" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="month" value="{{ __('Mês') }}" class="small mb-1" />
                    <input type="text" id="month" name="month" value="{{ old('month', '*') }}" class="form-control form-control-sm" style="width: 5rem;" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="weekday" value="{{ __('Dia da semana') }}" class="small mb-1" />
                    <input type="text" id="weekday" name="weekday" value="{{ old('weekday', '*') }}" class="form-control form-control-sm" style="width: 6rem;" required>
                </div>
                <div class="col">
                    <x-input-label for="command" value="{{ __('Comando') }}" class="small mb-1" />
                    <input type="text" id="command" name="command" value="{{ old('command') }}" class="form-control form-control-sm" placeholder="/usr/bin/php /home/.../script.php" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Criar') }}</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('minute')" class="mt-2" />
            <x-input-error :messages="$errors->get('hour')" class="mt-2" />
            <x-input-error :messages="$errors->get('day')" class="mt-2" />
            <x-input-error :messages="$errors->get('month')" class="mt-2" />
            <x-input-error :messages="$errors->get('weekday')" class="mt-2" />
            <x-input-error :messages="$errors->get('command')" class="mt-2" />
            <div class="form-text mt-2">
                {{ __('Sintaxe cron padrão: número, *, vírgula, hífen ou barra (ex: */5 * * * * roda a cada 5 minutos).') }}
            </div>
        </div>
    </div>

    <script>
        document.getElementById('preset').addEventListener('change', function () {
            if (! this.value) {
                return;
            }

            const [minute, hour, day, month, weekday] = this.value.split(' ');

            document.getElementById('minute').value = minute;
            document.getElementById('hour').value = hour;
            document.getElementById('day').value = day;
            document.getElementById('month').value = month;
            document.getElementById('weekday').value = weekday;
        });
    </script>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Agendamento') }}</th>
                        <th>{{ __('Comando') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->cronJobs as $job)
                        <tr>
                            <td><code>{{ $job->schedule() }}</code></td>
                            <td><code>{{ $job->command }}</code></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('client.hosting-accounts.cron.destroy', [$account, $job]) }}"
                                      onsubmit="return confirm('{{ __('Remove essa tarefa. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhuma tarefa cadastrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>
