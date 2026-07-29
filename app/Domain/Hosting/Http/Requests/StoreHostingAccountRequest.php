<?php

namespace App\Domain\Hosting\Http\Requests;

use App\Domain\Hosting\Models\HostingAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHostingAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', HostingAccount::class);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', Rule::exists('users', 'id')->where('type', 'client')],
            'server_id' => ['required', 'exists:servers,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'primary_domain' => [
                'required',
                'string',
                'regex:/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                Rule::unique('hosting_accounts', 'primary_domain'),
            ],
            'php_version' => ['required', Rule::in(config('hosting.php_versions'))],
            'create_database' => ['boolean'],
        ];
    }
}
