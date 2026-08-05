<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // Só contato/referência — não é mais credencial de login pra
            // ninguém, então nem precisa ser único.
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
        ];

        // username é a credencial de login do admin (cliente loga pelo
        // usuário da hospedagem, não tem users.username).
        if ($this->user()->isAdmin()) {
            $rules['username'] = [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique(User::class)->ignore($this->user()->id),
            ];
        }

        return $rules;
    }
}
