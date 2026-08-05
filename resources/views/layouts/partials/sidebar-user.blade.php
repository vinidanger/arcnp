<div class="border-t border-rail-line pt-2 mt-2">
    <x-dropdown align="start" :dropup="true">
        <x-slot name="trigger">
            <div class="flex items-center gap-2 p-2 rounded cursor-pointer hover:bg-white/5">
                <span class="flex items-center justify-center rounded-full bg-accent text-accent-ink font-semibold shrink-0 w-8 h-8 text-xs">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="sidebar-label text-sm truncate flex-1">{{ Auth::user()->name }}</span>
                <i class="bi bi-chevron-expand text-xs sidebar-label"></i>
            </div>
        </x-slot>

        <x-slot name="content">
            <x-dropdown-link :href="route('profile.edit')">
                <i class="bi bi-person"></i> {{ __('Perfil') }}
            </x-dropdown-link>

            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="bi bi-box-arrow-right"></i> {{ __('Sair') }}
                    </button>
                </form>
            </li>
        </x-slot>
    </x-dropdown>
</div>
