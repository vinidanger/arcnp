<?php

namespace App\Domain\Api\Http\Controllers\Admin;

use App\Domain\Api\Models\ApiClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiClientController extends Controller
{
    public function index()
    {
        $apiClients = ApiClient::orderBy('name')->get();

        return view('admin.api-clients.index', compact('apiClients'));
    }

    public function create()
    {
        return view('admin.api-clients.create');
    }

    public function docs()
    {
        return view('admin.api-clients.docs', ['endpoints' => $this->apiEndpointCatalog()]);
    }

    /**
     * Fonte única dos endpoints — usada pelo gerador de prompt de
     * integração (docs.blade.php), pra nunca ficar fora de sincronia
     * com o que routes/api-v1.php de fato expõe. Mesmo agrupamento/
     * ordem dos cards já escritos à mão na página.
     *
     * @return list<array{method: string, path: string, group: string, summary: string}>
     */
    private function apiEndpointCatalog(): array
    {
        return [
            ['method' => 'GET', 'path' => '/plans', 'group' => 'Descoberta', 'summary' => 'Lista os planos ativos.'],
            ['method' => 'GET', 'path' => '/servers', 'group' => 'Descoberta', 'summary' => 'Lista os servidores (pra saber o server_id antes de criar uma conta).'],
            ['method' => 'GET', 'path' => '/hosting-accounts', 'group' => 'Descoberta', 'summary' => 'Lista paginada de todas as contas — filtros: status, server_id, plan_id, search.'],

            ['method' => 'POST', 'path' => '/hosting-accounts', 'group' => 'Provisionamento', 'summary' => 'Cria um cliente novo e já provisiona a hospedagem numa chamada só.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}', 'group' => 'Provisionamento', 'summary' => 'Status atual de uma conta.'],
            ['method' => 'POST', 'path' => '/hosting-accounts/{id}/suspend', 'group' => 'Provisionamento', 'summary' => 'Suspende a conta (reversível).'],
            ['method' => 'POST', 'path' => '/hosting-accounts/{id}/reactivate', 'group' => 'Provisionamento', 'summary' => 'Reverte a suspensão.'],
            ['method' => 'DELETE', 'path' => '/hosting-accounts/{id}', 'group' => 'Provisionamento', 'summary' => 'Cancela e apaga a conta pra sempre (não reversível).'],

            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/domains', 'group' => 'Domínios e DNS', 'summary' => 'Domínios adicionais e subdomínios da conta.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/dns', 'group' => 'Domínios e DNS', 'summary' => 'Zona(s) DNS gerenciada(s) pelo Painel, com os registros aninhados.'],

            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/databases', 'group' => 'Banco de dados e Backups', 'summary' => 'Bancos MySQL da conta (sem senha).'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/backups', 'group' => 'Banco de dados e Backups', 'summary' => 'Histórico de backups da conta.'],

            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/mail', 'group' => 'E-mail', 'summary' => 'Domínios de e-mail, caixas (+ autorresposta/filtros) e encaminhamentos aninhados.'],

            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/malware-scans', 'group' => 'Segurança e proteções', 'summary' => 'Histórico de varreduras de malware.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/folder-protections', 'group' => 'Segurança e proteções', 'summary' => 'Pastas protegidas por senha (.htpasswd).'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/site-redirects', 'group' => 'Segurança e proteções', 'summary' => 'Regras de redirecionamento dos domínios da conta.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/hotlink-protection', 'group' => 'Segurança e proteções', 'summary' => 'Regras de proteção contra hotlink por domínio.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/mime-type-rules', 'group' => 'Segurança e proteções', 'summary' => 'Tipos MIME customizados por domínio.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/ssh-keys', 'group' => 'Segurança e proteções', 'summary' => 'Chaves públicas SSH autorizadas na conta.'],

            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/apps', 'group' => 'Apps e chamados', 'summary' => 'Instalações de app (WordPress/zip) e apps Node/Python.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/tickets', 'group' => 'Apps e chamados', 'summary' => 'Chamados de suporte da conta, com mensagens aninhadas.'],

            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/cron-jobs', 'group' => 'Outros', 'summary' => 'Tarefas agendadas (cron) da conta.'],
            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/ftp-accounts', 'group' => 'Outros', 'summary' => 'Contas FTP virtuais da conta (sem senha).'],

            ['method' => 'GET', 'path' => '/hosting-accounts/{id}/resources', 'group' => 'Recursos ao vivo', 'summary' => 'Uso de CPU/RAM/processos ao vivo, consultado no servidor (pode demorar alguns segundos).'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $apiClient = ApiClient::create(['name' => $data['name']]);

        $token = $apiClient->createToken('default')->plainTextToken;

        return redirect()
            ->route('admin.api-clients.index')
            ->with('plain_token', $token)
            ->with('status', 'Credencial criada. Copie o token abaixo — ele só aparece nesta tela agora.');
    }

    public function destroy(ApiClient $api_client)
    {
        $api_client->tokens()->delete();
        $api_client->delete();

        return redirect()->route('admin.api-clients.index')->with('status', 'Credencial revogada.');
    }
}
