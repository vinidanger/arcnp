<?php

namespace App\Support;

/**
 * Traduz os valores crus de enum (status, prioridade, agent_status,
 * ssl_status...) que vêm direto do banco pra rótulos em PT-BR — um mapa
 * único porque nenhum valor colide de sentido entre os diferentes enums
 * do projeto (ex.: "active" sempre quer dizer "Ativo", em qualquer tabela).
 */
class StatusLabel
{
    private const LABELS = [
        // hosting_accounts.status / domains.status / app_installations.status
        'creating' => 'Criando',
        'active' => 'Ativo',
        'suspended' => 'Suspenso',
        'error' => 'Erro',
        'deleted' => 'Excluído',
        'installing' => 'Instalando',

        // ssl_status
        'none' => 'Nenhum',
        'pending' => 'Pendente',
        'failed' => 'Falhou',

        // agent_jobs.status
        'queued' => 'Na fila',
        'sent' => 'Enviado',
        'running' => 'Em execução',
        'completed' => 'Concluído',

        // servers.agent_status
        'online' => 'Online',
        'offline' => 'Offline',

        // tickets.status
        'open' => 'Aberto',
        'answered' => 'Respondido',
        'closed' => 'Fechado',

        // tickets.priority
        'low' => 'Baixa',
        'normal' => 'Normal',
        'high' => 'Alta',

        // hosting_accounts.uptime_status / domains.uptime_status
        'up' => 'No ar',
        'down' => 'Fora do ar',
        'unknown' => 'Ainda não checado',
    ];

    public static function translate(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::LABELS[$value] ?? $value;
    }
}
