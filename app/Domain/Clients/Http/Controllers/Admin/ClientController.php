<?php

namespace App\Domain\Clients\Http\Controllers\Admin;

use App\Domain\Clients\Http\Requests\StoreClientRequest;
use App\Domain\Clients\Http\Requests\UpdateClientRequest;
use App\Http\Controllers\Controller;
use App\Models\User;

class ClientController extends Controller
{
    public function index()
    {
        $clients = User::where('type', 'client')->orderBy('name')->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'type' => 'client',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.clients.index')->with('status', 'Cliente criado.');
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
