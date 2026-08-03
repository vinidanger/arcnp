<div class="app-sidebar-footer border-top pt-2">
    <x-dropdown align="start" :dropup="true">
        <x-slot name="trigger">
            <div class="d-flex align-items-center gap-2 p-2 rounded" style="cursor: pointer;">
                <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-semibold flex-shrink-0"
                      style="width: 2rem; height: 2rem; font-size: 0.8rem;">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="sidebar-label small text-truncate flex-grow-1">{{ Auth::user()->name }}</span>
                <i class="bi bi-chevron-expand small sidebar-label"></i>
            </div>
        </x-slot>

        <x-slot name="content">
            <x-dropdown-link :href="route('profile.edit')">
                <i class="bi bi-person me-1"></i> {{ __('Perfil') }}
            </x-dropdown-link>

            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="bi bi-box-arrow-right me-1"></i> {{ __('Sair') }}
                    </button>
                </form>
            </li>
        </x-slot>
    </x-dropdown>
</div>
