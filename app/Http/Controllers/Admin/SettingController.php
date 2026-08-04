<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $maxUploadMb = (int) Setting::get('max_upload_mb', (string) config('hosting.max_upload_mb'));

        // Informativo pro admin: o valor salvo aqui só funciona até o
        // teto que o PHP-FPM do Painel já permite (upload_max_filesize
        // e post_max_size, o menor dos dois manda) — sem isso a UI não
        // avisa e o admin sobe o número achando que já resolveu.
        $phpUploadLimitMb = (int) floor(min(
            self::iniSizeToMb(ini_get('upload_max_filesize')),
            self::iniSizeToMb(ini_get('post_max_size')),
        ));

        $recaptchaEnabled = (bool) Setting::get('recaptcha_enabled');
        $recaptchaSiteKey = Setting::get('recaptcha_site_key', '');
        $recaptchaSecretKey = Setting::get('recaptcha_secret_key', '');

        return view('admin.settings.edit', compact(
            'maxUploadMb', 'phpUploadLimitMb', 'recaptchaEnabled', 'recaptchaSiteKey', 'recaptchaSecretKey',
        ));
    }

    private static function iniSizeToMb(string $value): float
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => $number * 1024,
            'k' => $number / 1024,
            'm' => $number,
            default => $number / 1048576,
        };
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'max_upload_mb' => ['required', 'integer', 'min:1', 'max:2048'],
            'recaptcha_enabled' => ['nullable', 'boolean'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::set('max_upload_mb', (string) $data['max_upload_mb']);
        Setting::set('recaptcha_enabled', $request->boolean('recaptcha_enabled') ? '1' : '0');
        Setting::set('recaptcha_site_key', $data['recaptcha_site_key'] ?? '');
        Setting::set('recaptcha_secret_key', $data['recaptcha_secret_key'] ?? '');

        return back()->with('status', 'Configurações salvas.');
    }
}
