<?php

namespace App\Domain\Api\Http\Controllers\Admin;

use App\Domain\Api\Models\ApiClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiClientController extends Controller
{
    public function index()
    {
        $apiClients = ApiClient::orderBy('name')->get();

        return view('admin.api-clients.index', compact('apiClients'));
    }

    public function create()
    {
        return view('admin.api-clients.create');
    }

    public function docs()
    {
        return view('admin.api-clients.docs');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $apiClient = ApiClient::create(['name' => $data['name']]);

        $token = $apiClient->createToken('default')->plainTextToken;

        return redirect()
            ->route('admin.api-clients.index')
            ->with('plain_token', $token)
            ->with('status', 'Credencial criada. Copie o token abaixo — ele só aparece nesta tela agora.');
    }

    public function destroy(ApiClient $api_client)
    {
        $api_client->tokens()->delete();
        $api_client->delete();

        return redirect()->route('admin.api-clients.index')->with('status', 'Credencial revogada.');
    }
}
