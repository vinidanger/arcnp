<nav class="navbar navbar-expand navbar-light bg-white border-bottom">
    <div class="container-fluid px-4 justify-content-end">
        <x-dropdown align="end">
            <x-slot name="trigger">
                <span class="nav-link d-flex align-items-center gap-2" style="cursor: pointer;">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-semibold"
                          style="width: 2rem; height: 2rem; font-size: 0.8rem;">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="d-none d-sm-inline">{{ Auth::user()->name }}</span>
                    <i class="bi bi-chevron-down small text-secondary"></i>
                </span>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">
                    <i class="bi bi-person me-1"></i> {{ __('Profile') }}
                </x-dropdown-link>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-1"></i> {{ __('Log Out') }}
                        </button>
                    </form>
                </li>
            </x-slot>
        </x-dropdown>
    </div>
</nav>
