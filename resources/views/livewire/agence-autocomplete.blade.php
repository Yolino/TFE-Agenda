<div class="relative" x-data="{ open: false }">
    <input type="text" wire:model.live.debounce.300ms="search"
        @focus="open = true"
        @click.outside="open = false"
        placeholder="Rechercher une agence..."
        class="input input-bordered input-sm w-64" />

    <div x-show="open" x-cloak
        class="absolute right-0 mt-1 z-20 bg-base-100 border border-base-300 rounded-box shadow-lg max-h-72 overflow-y-auto w-72">
        @if(strlen($search) === 0)
            <p class="px-4 py-2 text-xs text-base-content/50 italic">Tape pour chercher une agence...</p>
        @elseif($agences->isEmpty())
            <p class="px-4 py-2 text-xs text-base-content/50 italic">Aucune agence trouvée</p>
        @else
            @foreach($agences as $agence)
                <button type="button" wire:click="select({{ $agence->id }})"
                    @click="open = false"
                    class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">
                    <span class="font-medium">{{ $agence->display_name }}</span>
                    @if($agence->localite)
                        <span class="text-xs text-base-content/50 block">{{ $agence->localite }}</span>
                    @endif
                </button>
            @endforeach
        @endif
    </div>
</div>
