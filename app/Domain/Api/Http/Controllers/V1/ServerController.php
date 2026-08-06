<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\ServerResource;
use App\Domain\Servers\Models\Server;
use App\Http\Controllers\Controller;

class ServerController extends Controller
{
    public function index()
    {
        return ServerResource::collection(Server::orderBy('name')->get());
    }
}
