<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\FileManagerService;
use App\Http\Controllers\Controller;
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
            return redirect()->route('admin.hosting-accounts.show', $hosting_account)
                ->with('error', 'Falha ao listar arquivos: '.$e->getMessage());
        }

        return view('admin.hosting-accounts.files.index', [
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
            return redirect()->route('admin.hosting-accounts.files.index', [$hosting_account, 'path' => self::parentOf($path), 'root' => $root])
                ->with('error', 'Falha ao abrir arquivo: '.$e->getMessage());
        }

        return view('admin.hosting-accounts.files.edit', [
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

            return back()->with('status', 'Arquivo salvo.');
        } catch (Throwable $e) {
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

            return redirect()->route('admin.hosting-accounts.files.edit', [$hosting_account, 'path' => $path, 'root' => $data['root'] ?? null]);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar arquivo: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, HostingAccount $hosting_account, FileManagerService $files)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'path' => ['required', 'string'],
            'root' => ['nullable', 'string'],
        ]);

        try {
            $files->delete($hosting_account, $data['path'], $data['root'] ?? null);

            return redirect()->route('admin.hosting-accounts.files.index', [$hosting_account, 'path' => self::parentOf($data['path']), 'root' => $data['root'] ?? null])
                ->with('status', 'Removido.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover: '.$e->getMessage());
        }
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
