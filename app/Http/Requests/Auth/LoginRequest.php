<?php

namespace App\Http\Requests\Auth;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\SshAccessService;
use App\Models\Setting;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            // "login" aceita e-mail (admin, ou cliente sem hospedagem
            // ainda) OU o usuário Linux da hospedagem (cliente normal,
            // estilo cPanel/DirectAdmin) — por isso não tem regra "email".
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];

        // Só exige o captcha se o admin realmente configurou as duas
        // chaves — nunca quebra o login de quem não mexeu nisso ainda.
        if (Setting::get('recaptcha_enabled') && Setting::get('recaptcha_site_key') && Setting::get('recaptcha_secret_key')) {
            $rules['recaptcha_token'] = ['required', new Recaptcha];
        }

        return $rules;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $ok = str_contains($this->string('login'), '@')
            ? $this->attemptEmailLogin()
            : $this->attemptUsernameLogin();

        if (! $ok) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Caminho padrão do Breeze (admin, ou cliente que ainda não tem
     * hospedagem provisionada — só até lá o e-mail funciona; depois que a
     * hospedagem existe, o login "de verdade" passa a ser por username,
     * ver attemptUsernameLogin()).
     */
    private function attemptEmailLogin(): bool
    {
        $user = User::where('email', $this->string('login'))->first();

        if ($user && $user->isClient() && $user->hostingAccount) {
            // Depois que a hospedagem existe, a senha de verdade é a de
            // SSH (ver attemptUsernameLogin) — users.password fica
            // desatualizada assim que o cliente trocar a senha pelo
            // perfil, então não pode continuar valendo como login.
            return false;
        }

        return Auth::attempt(['email' => $this->string('login'), 'password' => $this->string('password')], $this->boolean('remember'));
    }

    /**
     * Login por usuário da hospedagem (linux_username) + senha de SSH —
     * não dá pra usar Auth::attempt() aqui porque o provider padrão do
     * Laravel sempre confere a senha contra users.password; a credencial
     * mora em hosting_accounts.ssh_password.
     */
    private function attemptUsernameLogin(): bool
    {
        $account = HostingAccount::where('linux_username', $this->string('login'))->first();

        if (! $account || ! SshAccessService::verifyPassword($account, $this->string('password'))) {
            return false;
        }

        $client = $account->client;

        if (! $client || ! $client->isClient() || $client->status !== 'active') {
            return false;
        }

        Auth::login($client, $this->boolean('remember'));

        return true;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login'))).'|'.$this->ip();
    }
}
