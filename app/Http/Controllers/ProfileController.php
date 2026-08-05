<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Rules\CurrentPasswordOrSsh;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Troca o template visual do painel — só cliente (admin nunca vê
     * essa tela mudar, ver plano). Revalida "não travado" no servidor
     * também, não confia só em esconder o campo na view.
     */
    public function updateTemplate(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isClient(), 404);
        abort_if($request->user()->ui_template_locked, 403, 'Seu administrador definiu o template do painel para sua conta.');

        $data = $request->validate([
            'ui_template' => ['required', 'in:default,cpanel'],
        ]);

        $request->user()->update(['ui_template' => $data['ui_template']]);

        return Redirect::route('profile.edit')->with('status', 'template-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', new CurrentPasswordOrSsh],
        ]);

        $user = $request->user();

        try {
            $user->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors([
                    'password' => 'Não é possível excluir: ainda existe uma hospedagem vinculada a esta conta.',
                ], 'userDeletion');
            }

            throw $e;
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
