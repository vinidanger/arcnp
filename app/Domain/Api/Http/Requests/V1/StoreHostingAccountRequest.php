<?php

namespace App\Domain\Api\Http\Requests\V1;

use App\Models\User;
use Closure;
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
            'client.email' => [
                'required',
                'string',
                'email',
                'max:255',
                // 1 cliente = 1 hospedagem, sempre — se o e-mail já
                // existe (endpoint reaproveita cliente existente, ver
                // controller) e já tem conta, não deixa criar outra.
                function (string $attribute, mixed $value, Closure $fail) {
                    $client = User::where('type', 'client')->where('email', $value)->first();

                    if ($client && $client->hostingAccount) {
                        $fail('Este cliente já tem uma hospedagem — não é possível criar outra.');
                    }
                },
            ],
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
