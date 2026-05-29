<div x-data="{ open: {{ request()->routeIs('mon-planning.*') || request()->routeIs('planning') ? 'true' : 'false' }}, sidebarOpen: window.innerWidth > 768 }" x-init="() => { sidebarOpen = window.innerWidth > 768; $el.classList.remove('invisible') }" x-show="sidebarOpen" @toggle-sidebar.window="sidebarOpen = !sidebarOpen" x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="z-10 bg-secondary w-72 space-y-6 py-7 px-2 fixed inset-y-0 left-0 transform md:relative md:translate-x-0 invisible flex-shrink-0">
    <a href="/" class="flex justify-center px-4 mb-2">
        <img src="/images/logo.png" alt="logo" class="h-[120px] w-auto max-w-full object-contain">
    </a>
    <nav>
        <div @click="open = !open" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] cursor-pointer text-white">
            <i class="fa-duotone fa-calendar-days text-lg w-6 text-center shrink-0"></i>
            <span>Planning</span>
        </div>
        <div x-show="open" class="opacity-80 p-2 space-y-2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90">
            <a href="{{ route('mon-planning.index') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
                <i class="fa-duotone fa-calendar-days text-lg w-6 text-center shrink-0"></i>
                <span>Mon planning</span>
            </a>
            <a href="{{ route('planning') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
                <i class="fa-duotone fa-calendar-days text-lg w-6 text-center shrink-0"></i>
                <span>Crocheux</span>
            </a>
        </div>
        <a href="{{ route('mes-conges.index') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
            <i class="fa-duotone fa-person-walking-luggage text-lg w-6 text-center shrink-0"></i>
            <span>Mes congés</span>
        </a>
        <a href="{{ route('justificatif-absence.index') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
            <i class="fa-duotone fa-face-head-bandage text-lg w-6 text-center shrink-0"></i>
            <span>Justificatifs d'absence</span>
        </a>
        @if(Auth::user()->is_admin())
            @php
                $nbCongesEnAttente = \App\Models\DemandeConge::where('status', 'envoyee')->count();
            @endphp
            <a href="{{ route('admin.conges') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
                <i class="fa-duotone fa-clipboard-check text-lg w-6 text-center shrink-0"></i>
                <span>Gestion des congés</span>
                @if($nbCongesEnAttente > 0)
                    <span class="badge badge-sm badge-warning ml-auto">{{ $nbCongesEnAttente }}</span>
                @endif
            </a>
            <a href="{{ route('admin.absences') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
                <i class="fa-duotone fa-clipboard-medical text-lg w-6 text-center shrink-0"></i>
                <span>Gestion des absences</span>
            </a>
            <a href="{{ route('admin.user') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
                <i class="fa-duotone fa-users text-lg w-6 text-center shrink-0"></i>
                <span>Utilisateurs</span>
            </a>
        @endif
        {{-- Logs système : visible pour les Admins ET les Directeurs --}}
        @if(Auth::user()->canAccessLogs())
            <a href="{{ route('admin.logs') }}" class="flex items-center gap-3 py-3 px-5 rounded-box transition duration-200 text-[15px] text-white">
                <i class="fa-duotone fa-list-timeline text-lg w-6 text-center shrink-0"></i>
                <span>Logs système</span>
            </a>
        @endif
    </nav>
</div>