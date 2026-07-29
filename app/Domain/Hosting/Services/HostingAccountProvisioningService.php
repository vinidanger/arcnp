<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\HostingDatabase;
use App\Domain\Hosting\Support\SubdirectoryGenerator;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Orquestra a sequência de Actions no Agent para provisionar (ou
 * desprovisionar) uma conta de hospedagem. Cada chamada ao Agent é
 * síncrona e registrada como um AgentJob (auditoria). Se um passo
 * falhar no meio da sequência, desfaz best-effort o que já foi criado
 * antes de marcar a conta como erro — não é uma transação atômica de
 * verdade (não existe isso entre dois processos), é compensação.
 */
class HostingAccountProvisioningService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function provision(HostingAccount $account): void
    {
        $server = $account->server;
        $username = $account->linux_username;
        $domain = $account->primary_domain;

        $completed = [];

        try {
            $this->runStep($server, 'linux.create_user', ['username' => $username]);
            $completed[] = 'user';

            $this->runStep($server, 'php.create_pool', ['username' => $username]);
            $completed[] = 'pool';

            $this->runStep($server, 'web.create_vhost', ['username' => $username, 'domain' => $domain]);
            $completed[] = 'vhost';

            $account->update(['status' => 'active', 'last_provision_error' => null]);
        } catch (Throwable $e) {
            $this->rollback($server, $completed, $username, $domain);

            $account->update(['status' => 'error', 'last_provision_error' => $e->getMessage()]);
        }
    }

    /**
     * Remove tudo que existir no Agent para esta conta (usada tanto na
     * exclusão real quanto no rollback de uma provisão que falhou).
     */
    public function deprovision(HostingAccount $account): void
    {
        if ($account->database) {
            $this->deleteDatabase($account);
        }

        foreach ($account->domains as $domain) {
            $this->removeDomain($domain);
        }

        $this->rollback($account->server, ['vhost', 'pool', 'user'], $account->linux_username, $account->primary_domain);
    }

    /**
     * Domínio adicional/subdomínio — reaproveita usuário Linux e pool
     * PHP-FPM da conta, só ganha subdiretório e vhost próprios.
     */
    public function addDomain(HostingAccount $account, string $domainName, string $type): Domain
    {
        $subdir = SubdirectoryGenerator::fromDomain($account, $domainName);

        $domain = $account->domains()->create([
            'domain' => $domainName,
            'type' => $type,
            'subdirectory' => $subdir,
            'status' => 'creating',
        ]);

        try {
            $this->runStep($account->server, 'web.create_addon_domain', [
                'username' => $account->linux_username,
                'domain' => $domainName,
                'subdir' => $subdir,
            ]);

            $domain->update(['status' => 'active']);
        } catch (Throwable $e) {
            $domain->update(['status' => 'error', 'last_error' => $e->getMessage()]);
        }

        return $domain->fresh();
    }

    public function removeDomain(Domain $domain): void
    {
        $account = $domain->hostingAccount;

        $this->client->dispatch($account->server, 'web.delete_addon_domain', [
            'username' => $account->linux_username,
            'domain' => $domain->domain,
            'subdir' => $domain->subdirectory,
        ]);

        $domain->delete();
    }

    /**
     * Banco é opcional e por conta (1:1 nesta fase). Usa o mesmo
     * username Linux como nome do banco/usuário MySQL — já validado
     * pelo UsernameGenerator e compatível com a regex do Agent.
     */
    public function provisionDatabase(HostingAccount $account): HostingDatabase
    {
        $dbName = $account->linux_username;
        $dbUsername = $account->linux_username;
        $dbPassword = Str::password(24);

        $this->runStep($account->server, 'database.create_mysql', [
            'db_name' => $dbName,
            'db_username' => $dbUsername,
            'db_password' => $dbPassword,
        ]);

        return $account->database()->create([
            'db_name' => $dbName,
            'db_username' => $dbUsername,
            'db_password' => $dbPassword,
        ]);
    }

    public function deleteDatabase(HostingAccount $account): void
    {
        $database = $account->database;

        if (! $database) {
            return;
        }

        $this->client->dispatch($account->server, 'database.delete_mysql', [
            'db_name' => $database->db_name,
            'db_username' => $database->db_username,
        ]);

        $database->delete();
    }

    /**
     * Assíncrona — só dispara e marca "pending". O resultado final
     * (ativo/falhou) chega depois via callback do Agent, tratado em
     * AgentWebhookController::callback().
     */
    public function issueSslCertificate(HostingAccount $account): void
    {
        $this->client->dispatch($account->server, 'ssl.issue_certificate', [
            'username' => $account->linux_username,
            'domain' => $account->primary_domain,
        ]);

        $account->update(['ssl_status' => 'pending', 'ssl_error' => null]);
    }

    public function suspend(HostingAccount $account): void
    {
        $this->runStep($account->server, 'hosting.suspend', [
            'username' => $account->linux_username,
            'domain' => $account->primary_domain,
        ]);

        $account->update(['status' => 'suspended']);
    }

    public function reactivate(HostingAccount $account): void
    {
        $this->runStep($account->server, 'hosting.reactivate', [
            'username' => $account->linux_username,
            'domain' => $account->primary_domain,
        ]);

        $account->update(['status' => 'active']);
    }

    private function runStep(Server $server, string $action, array $payload): void
    {
        $job = $this->client->dispatch($server, $action, $payload);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? "Falha ao executar {$action}");
        }
    }

    private function rollback(Server $server, array $completedSteps, string $username, string $domain): void
    {
        if (in_array('vhost', $completedSteps, true)) {
            $this->client->dispatch($server, 'web.delete_vhost', ['domain' => $domain]);
        }

        if (in_array('pool', $completedSteps, true)) {
            $this->client->dispatch($server, 'php.delete_pool', ['username' => $username]);
        }

        if (in_array('user', $completedSteps, true)) {
            $this->client->dispatch($server, 'linux.delete_user', ['username' => $username]);
        }
    }
}
