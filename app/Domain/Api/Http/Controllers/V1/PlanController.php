<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\PlanResource;
use App\Domain\Hosting\Models\Plan;
use App\Http\Controllers\Controller;

class PlanController extends Controller
{
    public function index()
    {
        return PlanResource::collection(Plan::where('is_active', true)->orderBy('name')->get());
    }
}
