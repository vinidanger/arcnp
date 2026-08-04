<?php

namespace App\Domain\Support\Http\Controllers\Client;

use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Services\TicketService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = $request->user()->tickets()
            ->with('hostingAccount')
            ->latest('updated_at')
            ->paginate(20);

        return view('client.tickets.index', ['tickets' => $tickets]);
    }

    public function create(Request $request)
    {
        return view('client.tickets.create', ['accounts' => $request->user()->hostingAccounts]);
    }

    public function store(Request $request, TicketService $tickets)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'in:low,normal,high'],
            'hosting_account_id' => ['nullable', 'integer', 'exists:hosting_accounts,id'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $account = $data['hosting_account_id']
            ? $request->user()->hostingAccounts()->findOrFail($data['hosting_account_id'])
            : null;

        $ticket = $tickets->create($request->user(), $data['subject'], $data['priority'], $account, $data['body']);

        return redirect()->route('client.tickets.show', $ticket)->with('status', 'Chamado aberto.');
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load('messages.user', 'hostingAccount');

        return view('client.tickets.show', ['ticket' => $ticket]);
    }

    public function storeMessage(Request $request, Ticket $ticket, TicketService $tickets)
    {
        $this->authorize('update', $ticket);

        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        try {
            $tickets->reply($ticket, $request->user(), $data['body']);

            return back()->with('status', 'Resposta enviada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao responder: '.$e->getMessage());
        }
    }

    public function close(Ticket $ticket, TicketService $tickets)
    {
        $this->authorize('update', $ticket);

        $tickets->close($ticket);

        return back()->with('status', 'Chamado fechado.');
    }
}
