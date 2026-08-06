<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\TicketResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $tickets = $hosting_account->tickets()->with('messages.user')->get();

        return TicketResource::collection($tickets);
    }
}
