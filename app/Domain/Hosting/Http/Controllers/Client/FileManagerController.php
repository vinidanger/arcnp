<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\FileManagerService;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class FileManagerController extends Controller
{
    public function index(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('view', $hosting_account);

        $path = (string) $request->query('path', '');
        $root = $this->rootFrom($request);

        try {
            $result = $files->list($hosting_account, $path, $root);
        } catch (Throwable $e) {
            return redirect()->route('client.hosting-accounts.show', $hosting_account)
                ->with('error', 'Falha ao listar arquivos: '.$e->getMessage());
        }

        return view('client.hosting-accounts.files.index', [
            'account' => $hosting_account,
            'path' => $result['path'],
            'entries' => $result['entries'],
            'root' => $root,
            'outsideDomains' => $this->outsideDomains($hosting_account),
        ]);
    }

    public function edit(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('view', $hosting_account);

        $path = (string) $request->query('path', '');
        $root = $this->rootFrom($request);

        try {
            $result = $files->read($hosting_account, $path, $root);
        } catch (Throwable $e) {
            return redirect()->route('client.hosting-accounts.files.index', [$hosting_account, 'path' => self::parentOf($path), 'root' => $root])
                ->with('error', 'Falha ao abrir arquivo: '.$e->getMessage());
        }

        return view('client.hosting-accounts.files.edit', [
            'account' => $hosting_account,
            'path' => $result['path'],
            'content' => $result['content'],
            'root' => $root,
        ]);
    }

    public function update(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'path' => ['required', 'string'],
            'content' => ['nullable', 'string'],
            'root' => ['nullable', 'string'],
        ]);

        try {
            $files->write($hosting_account, $data['path'], $data['content'] ?? '', $data['root'] ?? null);

            // O editor de código salva via fetch() (ver code-editor.js)
            // pra não recarregar a página inteira — perderia a posição
            // de rolagem em arquivos grandes. expectsJson() só é
            // verdadeiro nesse caminho (a requisição manda
            // Accept: application/json); um <form> normal sem JS
            // continua caindo no redirect de sempre.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Arquivo salvo.']);
            }

            return back()->with('status', 'Arquivo salvo.');
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Falha ao salvar: '.$e->getMessage()], 422);
            }

            return back()->with('error', 'Falha ao salvar: '.$e->getMessage());
        }
    }

    public function storeDirectory(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'current_path' => ['nullable', 'string'],
            'name' => ['required', 'string', 'regex:/^[^\/\\\\]+$/'],
            'root' => ['nullable', 'string'],
        ]);

        $path = trim(($data['current_path'] ?? '').'/'.$data['name'], '/');

        try {
            $files->createDirectory($hosting_account, $path, $data['root'] ?? null);

            return back()->with('status', 'Pasta criada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar pasta: '.$e->getMessage());
        }
    }

    public function storeFile(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'current_path' => ['nullable', 'string'],
            'name' => ['required', 'string', 'regex:/^[^\/\\\\]+$/'],
            'root' => ['nullable', 'string'],
        ]);

        $path = trim(($data['current_path'] ?? '').'/'.$data['name'], '/');

        try {
            $files->createFile($hosting_account, $path, $data['root'] ?? null);

            return redirect()->route('client.hosting-accounts.files.edit', [$hosting_account, 'path' => $path, 'root' => $data['root'] ?? null]);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar arquivo: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'paths' => ['required', 'array', 'min:1'],
            'paths.*' => ['string'],
            'current_path' => ['nullable', 'string'],
            'root' => ['nullable', 'string'],
        ]);

        $removed = 0;
        $errors = [];

        foreach ($data['paths'] as $path) {
            try {
                $files->delete($hosting_account, $path, $data['root'] ?? null);
                $removed++;
            } catch (Throwable $e) {
                $errors[] = "{$path}: {$e->getMessage()}";
            }
        }

        $redirect = redirect()->route('client.hosting-accounts.files.index', [
            $hosting_account, 'path' => $data['current_path'] ?? '', 'root' => $data['root'] ?? null,
        ]);

        if ($removed > 0) {
            $redirect->with('status', $removed > 1 ? "{$removed} itens removidos." : 'Removido.');
        }

        if ($errors) {
            $redirect->with('error', 'Falha ao remover: '.implode(' | ', $errors));
        }

        return $redirect;
    }

    public function rename(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'from' => ['required', 'string'],
            'name' => ['required', 'string', 'regex:/^[^\/\\\\]+$/'],
            'root' => ['nullable', 'string'],
        ]);

        $parent = self::parentOf($data['from']);
        $to = $parent === '' ? $data['name'] : "{$parent}/{$data['name']}";

        try {
            $files->rename($hosting_account, $data['from'], $to, $data['root'] ?? null);

            return back()->with('status', 'Renomeado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao renomear: '.$e->getMessage());
        }
    }

    public function upload(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $maxUploadMb = (int) Setting::get('max_upload_mb', (string) config('hosting.max_upload_mb'));

        $data = $request->validate([
            'current_path' => ['nullable', 'string'],
            'root' => ['nullable', 'string'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:'.($maxUploadMb * 1024)],
        ]);

        $root = $data['root'] ?? null;
        $currentPath = $data['current_path'] ?? '';
        $uploaded = 0;
        $errors = [];

        foreach ($request->file('files') as $uploadedFile) {
            $name = $uploadedFile->getClientOriginalName();
            $path = trim($currentPath.'/'.$name, '/');

            try {
                $files->upload($hosting_account, $path, file_get_contents($uploadedFile->getRealPath()), $root);
                $uploaded++;
            } catch (Throwable $e) {
                $errors[] = "{$name}: {$e->getMessage()}";
            }
        }

        return response()->json(['uploaded' => $uploaded, 'errors' => $errors]);
    }

    public function compress(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'current_path' => ['nullable', 'string'],
            'root' => ['nullable', 'string'],
            'paths' => ['required', 'array', 'min:1'],
            'paths.*' => ['string'],
            'output' => ['required', 'string', 'regex:/^[^\/\\\\]+$/'],
        ]);

        $output = preg_replace('/\.zip$/i', '', $data['output']).'.zip';
        $outputPath = trim(($data['current_path'] ?? '').'/'.$output, '/');

        try {
            $files->compress($hosting_account, $data['paths'], $outputPath, $data['root'] ?? null);

            return back()->with('status', "Compactado em {$output}.");
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao compactar: '.$e->getMessage());
        }
    }

    public function extract(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'path' => ['required', 'string'],
            'root' => ['nullable', 'string'],
        ]);

        try {
            $files->extract($hosting_account, $data['path'], self::parentOf($data['path']), $data['root'] ?? null);

            return back()->with('status', 'Extraído.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao extrair: '.$e->getMessage());
        }
    }

    public function download(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('view', $hosting_account);

        $path = (string) $request->query('path', '');
        $root = $this->rootFrom($request);

        return $files->download($hosting_account, $path, $root);
    }

    private function rootFrom(Request $request): ?string
    {
        $root = $request->query('root');

        return blank($root) ? null : (string) $root;
    }

    private function outsideDomains(HostingAccount $hosting_account): Collection
    {
        return $hosting_account->domains->where('location', 'outside_public_html');
    }

    private static function parentOf(string $path): string
    {
        $parent = dirname($path);

        return $parent === '.' ? '' : $parent;
    }
}
