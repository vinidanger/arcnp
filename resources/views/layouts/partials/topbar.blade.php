<nav class="navbar navbar-expand navbar-light bg-white border-bottom">
    <div class="container-fluid px-4 justify-content-end">
        <x-dropdown align="end">
            <x-slot name="trigger">
                <span class="nav-link" style="cursor: pointer;">{{ Auth::user()->name }}</span>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </li>
            </x-slot>
        </x-dropdown>
    </div>
</nav>
