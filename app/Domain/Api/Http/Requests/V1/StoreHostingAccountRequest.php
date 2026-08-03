<?php

namespace App\Domain\Api\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHostingAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client.name' => ['required', 'string', 'max:255'],
            'client.email' => ['required', 'string', 'email', 'max:255'],
            'client.password' => ['nullable', 'string', 'min:8'],
            'server_id' => ['required', 'integer', 'exists:servers,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'primary_domain' => [
                'required',
                'string',
                'regex:/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                Rule::unique('hosting_accounts', 'primary_domain'),
            ],
            'php_version' => ['required', Rule::in(config('hosting.php_versions'))],
        ];
    }
}
