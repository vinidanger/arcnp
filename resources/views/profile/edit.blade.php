<x-app-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Profile') }}</h1>
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
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
