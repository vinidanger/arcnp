<?php

namespace App\Domain\Clients\Http\Controllers\Admin;

use App\Domain\Clients\Http\Requests\StoreClientRequest;
use App\Domain\Clients\Http\Requests\UpdateClientRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = User::where('type', 'client')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        $client = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'type' => 'client',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Cliente sem hospedagem não serve pra nada nesse painel — manda
        // o admin direto criar a conta dele, já pré-selecionado.
        return redirect()
            ->route('admin.hosting-accounts.create', ['client' => $client->id])
            ->with('status', 'Cliente criado — agora crie a hospedagem dele.');
    }

    public function edit(User $client)
    {
        abort_unless($client->isClient(), 404);

        return view('admin.clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, User $client)
    {
        abort_unless($client->isClient(), 404);

        $data = $request->validated();

        $client->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $client->update(['password' => $data['password']]);
        }

        return redirect()->route('admin.clients.index')->with('status', 'Cliente atualizado.');
    }
}
