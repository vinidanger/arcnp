<?php

namespace App\Domain\Servers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Domain\Servers\Models\Server::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'public_ip_address' => ['nullable', 'ip'],
            'dns_ns1' => ['nullable', 'string', 'max:255'],
            'dns_ns2' => ['nullable', 'string', 'max:255'],
            'mail_hostname' => ['nullable', 'string', 'max:255'],
            'ftp_hostname' => ['nullable', 'string', 'max:255'],
            'agent_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'use_tls' => ['boolean'],
            'os' => ['nullable', 'string', 'max:255'],
            'cpu_cores' => ['nullable', 'integer', 'min:1'],
            'memory_mb' => ['nullable', 'integer', 'min:1'],
            'disk_gb' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
