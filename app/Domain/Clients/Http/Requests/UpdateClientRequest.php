<?php

namespace App\Domain\Clients\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Só contato/referência, sem exigir unicidade — ver StoreClientRequest.
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'status' => ['required', 'in:active,suspended'],
        ];
    }
}
