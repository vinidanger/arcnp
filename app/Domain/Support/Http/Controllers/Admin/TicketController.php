<?php

namespace App\Domain\Support\Http\Controllers\Admin;

use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Services\TicketService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $status = $request->query('status');

        $tickets = Ticket::with('user', 'hostingAccount')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tickets.index', ['tickets' => $tickets, 'status' => $status]);
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load('messages.user', 'user', 'hostingAccount');

        return view('admin.tickets.show', ['ticket' => $ticket]);
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

    public function reopen(Ticket $ticket, TicketService $tickets)
    {
        $this->authorize('reopen', $ticket);

        $tickets->reopen($ticket);

        return back()->with('status', 'Chamado reaberto.');
    }
}
