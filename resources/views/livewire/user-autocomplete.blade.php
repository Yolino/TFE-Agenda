<div class="relative" x-data="{ open: false }">
    <input type="text" wire:model.live.debounce.300ms="search"
        @focus="open = true"
        @click.outside="open = false"
        placeholder="{{ $currentLabel !== '' ? $currentLabel : 'Rechercher un utilisateur...' }}"
        class="input input-bordered input-sm w-72" />

    <div x-show="open" x-cloak
        class="absolute left-0 mt-1 z-20 bg-base-100 border border-base-300 rounded-box shadow-lg max-h-72 overflow-y-auto w-72">
        @if(strlen($search) === 0)
            <p class="px-4 py-2 text-xs text-base-content/50 italic">Tape pour chercher un utilisateur...</p>
        @elseif($users->isEmpty())
            <p class="px-4 py-2 text-xs text-base-content/50 italic">Aucun utilisateur trouvé</p>
        @else
            @foreach($users as $user)
                <button type="button" wire:click="select({{ $user->id }})"
                    @click="open = false"
                    class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm
                           {{ $user->id === $selectedUserId ? 'bg-base-200 font-semibold' : '' }}">
                    <span class="font-medium">{{ $user->firstname }} {{ $user->name }}</span>
                    <span class="text-xs text-base-content/50 block">{{ $user->email }}</span>
                </button>
            @endforeach
        @endif
    </div>
</div>
