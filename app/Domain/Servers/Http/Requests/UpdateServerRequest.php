<?php

namespace App\Domain\Servers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('server'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'agent_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'use_tls' => ['boolean'],
            'os' => ['nullable', 'string', 'max:255'],
            'cpu_cores' => ['nullable', 'integer', 'min:1'],
            'memory_mb' => ['nullable', 'integer', 'min:1'],
            'disk_gb' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
