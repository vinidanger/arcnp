<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Template') }}</h1>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <div style="max-width: 32rem;">
                @include('profile.partials.update-template-form')
            </div>
        </div>
    </div>
</x-client-layout>
