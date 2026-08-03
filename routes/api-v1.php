<?php

use App\Domain\Api\Http\Controllers\V1\HostingAccountController;
use App\Domain\Api\Http\Controllers\V1\PlanController;
use Illuminate\Support\Facades\Route;

// Machine-to-machine: sistema externo (ex: outro painel) -> Painel,
// autenticado por token Sanctum (ver Admin > Integrações de API). Todo
// token tem acesso completo — sem escopo por conta nessa v1.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');

    Route::post('hosting-accounts', [HostingAccountController::class, 'store'])->name('hosting-accounts.store');
    Route::get('hosting-accounts/{hosting_account}', [HostingAccountController::class, 'show'])->name('hosting-accounts.show');
    Route::post('hosting-accounts/{hosting_account}/suspend', [HostingAccountController::class, 'suspend'])->name('hosting-accounts.suspend');
    Route::post('hosting-accounts/{hosting_account}/reactivate', [HostingAccountController::class, 'reactivate'])->name('hosting-accounts.reactivate');
    Route::delete('hosting-accounts/{hosting_account}', [HostingAccountController::class, 'destroy'])->name('hosting-accounts.destroy');
});
