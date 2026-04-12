<div class="navbar bg-base-100">
    <div class="flex-1 pl-3">
        <div class="btn btn-ghost" @click="$dispatch('toggle-sidebar')">
            <i class="text-2xl cursor-pointer" :class="window.innerWidth > 768 ? (isSidebarOpen ? 'fa-duotone fa-arrow-left' : 'fa-duotone fa-arrow-right') : 'fa-duotone fa-bars'"></i>
            <span x-text="window.innerWidth > 768 ? (isSidebarOpen ? 'Menu' : 'Menu') : ''"></span>
        </div>
    </div>
    <div class="flex-none gap-2">
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-ghost btn-circle float-right">
                <span class="w-10 rounded-full bg-accent aspect-square text-white flex items-center justify-center">
                    {{ auth()->user()->firstname[0] . auth()->user()->name[0] }}
                </span>
            </label>
            <ul tabindex="0" class="mt-14 z-[1] p-2 menu menu-md dropdown-content bg-base-100 w-52">
                <li><a href="/mon-profile"><i class="fa-duotone fa-user mr-1"></i> Mon profile</a></li>
                <li><a href="/logout"><i class="fa-duotone fa-arrow-right-from-bracket mr-1"></i> Se déconnecter</a></li>
            </ul>
        </div>
    </div>
</div>