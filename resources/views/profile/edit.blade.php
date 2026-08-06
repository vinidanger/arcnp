{{-- /profile é compartilhado entre admin e cliente — usa o mesmo layout
     (sidebar, tema, etc.) de onde o usuário logado realmente navega, em
     vez do layout antigo do scaffold Breeze (layouts.app), que nunca
     recebeu o redesign visual do resto do painel. --}}
@php $layout = auth()->user()->isAdmin() ? 'admin-layout' : 'client-layout'; @endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Perfil') }}</h1>
    </x-slot>

    <div class="d-flex flex-column gap-3">
        <div class="card">
            <div class="card-body">
                <div style="max-width: 32rem;">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div style="max-width: 32rem;">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div style="max-width: 32rem;">
                    @include('profile.partials.two-factor-form')
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div style="max-width: 32rem;">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
