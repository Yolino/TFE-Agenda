<div x-data="{ open: false, sidebarOpen: window.innerWidth > 768 }" x-init="() => { sidebarOpen = window.innerWidth > 768; $el.classList.remove('invisible') }" x-show="sidebarOpen" @toggle-sidebar.window="sidebarOpen = !sidebarOpen" @click.away="if (window.innerWidth <= 768) sidebarOpen = false" x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="z-10 bg-secondary w-72 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform md:relative invisible">
    <a href="/" class="text-white flex items-center space-x-2 px-4">
        <img class="mb-5" src="/images/logo_w.png" alt="logo">
    </a>
    <nav>
        <!-- <div @click="open = !open" class="block py-2.5 px-4 rounded-box transition duration-200 cursor-pointer">
            <i class="fa-duotone fa-network-wired mr-2"></i> Dropdown
        </div>
        <div x-show="open" class="opacity-80 p-2 space-y-2 " x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
            <a href="#" class="block py-1 px-2 rounded-box transition text-base-100 duration-200">
                <i class="fa-duotone fa-horizontal-rule mr-2 opacity-50"></i> Sublevel 1
            </a>
        </div> -->
        <div @click="open = !open" class="flex items-center gap-3 py-2.5 px-4 rounded-box transition duration-200 cursor-pointer text-white">
            <i class="fa-duotone fa-calendar-days w-6 text-center shrink-0"></i>
            <span>Planning</span>
        </div>
        <div x-show="open" class="opacity-80 p-2 space-y-2 " x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
            <a href="{{ route('mon-planning.index') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-box transition duration-200 text-white">
                <i class="fa-duotone fa-calendar-days w-6 text-center shrink-0"></i>
                <span>Mon planning</span>
            </a>
            <a href="{{ route('planning') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-box transition duration-200 text-white">
                <i class="fa-duotone fa-calendar-days w-6 text-center shrink-0"></i>
                <span>Crocheux</span>
            </a>
        </div>
        <a href="{{ route('mes-conges.index') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-box transition duration-200 text-white">
            <i class="fa-duotone fa-solid fa-person-walking-luggage fa-lg w-6 text-center shrink-0"></i>
            <span>Mes congés</span>
        </a>
        <a href="{{ route('justificatif-absence.index') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-box transition duration-200 text-white">
            <i class="fa-duotone fa-solid fa-face-head-bandage fa-lg w-6 text-center shrink-0"></i>
            <span>Justificatifs d'absence</span>
        </a>
        @if(Auth::user()->is_admin())
            @php
                $nbCongesEnAttente = \App\Models\DemandeConge::where('status', 'envoyee')->count();
            @endphp
            <a href="{{ route('admin.conges') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-box transition duration-200 text-white">
                <i class="fa-duotone fa-clipboard-check w-6 text-center shrink-0"></i>
                <span>Gestion des congés</span>
                @if($nbCongesEnAttente > 0)
                    <span class="badge badge-sm badge-warning ml-auto">{{ $nbCongesEnAttente }}</span>
                @endif
            </a>
            <a href="{{ route('admin.user') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-box transition duration-200 text-white">
                <i class="fa-duotone fa-user w-6 text-center shrink-0"></i>
                <span>Utilisateurs</span>
            </a>
        @endif
        <!--
        <a href="{{ route('parametres') }}" class="block py-2.5 px-4 rounded-box transition duration-200 text-white">
            <i class="fa-duotone fa-gear-complex mr-3"></i> Paramètres
        </a>-->
    </nav>
</div>