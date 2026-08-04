<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\MimeTypeRule;
use App\Domain\Hosting\Services\MimeTypeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class MimeTypeController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('mimeTypeRules', 'domains');

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        return view('admin.hosting-accounts.mime-types.index', ['account' => $hosting_account, 'domains' => $domains]);
    }

    public function store(Request $request, HostingAccount $hosting_account, MimeTypeService $mimeTypes)
    {
        $this->authorize('update', $hosting_account);

        $domains = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());

        $data = $request->validate([
            'domain' => ['required', 'string', 'in:'.implode(',', $domains)],
            'extension' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9.]+$/'],
            'mime_type' => ['required', 'string', 'max:100', 'regex:~^[a-zA-Z0-9][a-zA-Z0-9!#$&.+\-^_]*/[a-zA-Z0-9][a-zA-Z0-9!#$&.+\-^_]*$~'],
        ]);

        try {
            $mimeTypes->create($hosting_account, $data['domain'], $data['extension'], $data['mime_type']);

            return back()->with('status', 'Regra de MIME type criada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar regra: '.$e->getMessage());
        }
    }

    public function destroy(HostingAccount $hosting_account, MimeTypeRule $mime_type_rule, MimeTypeService $mimeTypes)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($mime_type_rule->hosting_account_id === $hosting_account->id, 404);

        try {
            $mimeTypes->delete($mime_type_rule);

            return back()->with('status', 'Regra removida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover regra: '.$e->getMessage());
        }
    }
}
