<?php

namespace App\Domain\Clients\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Só contato/referência — cliente loga pelo usuário da
            // hospedagem, não por e-mail, então não precisa ser único.
            // Sem campo de senha aqui: a credencial de login real do
            // cliente (mesma do SSH) só existe depois que a hospedagem é
            // provisionada — ver HostingAccountProvisioningService.
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
