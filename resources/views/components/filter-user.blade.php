@props([
    'results' => collect(),
    'search' => '',
    'selectedLabel' => '',
    'searchProp' => 'userSearch',
    'selectMethod' => 'selectUser',
    'clearMethod' => 'clearUserFilter',
    'placeholder' => 'Rechercher un utilisateur...',
])

<div class="flex flex-wrap gap-2 items-center">
    <span class="text-sm font-semibold">Utilisateur :</span>

    @if($selectedLabel !== '')
        <span class="badge badge-lg badge-primary gap-2">
            <i class="fa-duotone fa-user"></i>
            {{ $selectedLabel }}
            <button type="button" wire:click="{{ $clearMethod }}"
                    class="hover:text-error" aria-label="Retirer le filtre utilisateur">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </span>
    @else
        <div class="relative" x-data="{ open: false }">
            <input type="text"
                   wire:model.live.debounce.300ms="{{ $searchProp }}"
                   @focus="open = true"
                   @click.outside="open = false"
                   placeholder="{{ $placeholder }}"
                   autocomplete="off"
                   class="input input-bordered input-sm w-64" />

            <div x-show="open" x-cloak
                 class="absolute left-0 mt-1 z-30 bg-base-100 border border-base-300 rounded-box shadow-lg max-h-72 overflow-y-auto w-64">
                @if(strlen(trim($search)) === 0)
                    <p class="px-4 py-2 text-xs text-base-content/50 italic">Tape pour chercher un utilisateur...</p>
                @elseif($results->isEmpty())
                    <p class="px-4 py-2 text-xs text-base-content/50 italic">Aucun utilisateur trouvé</p>
                @else
                    @foreach($results as $u)
                        <button type="button"
                                wire:click="{{ $selectMethod }}({{ $u->id }})"
                                @click="open = false"
                                class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">
                            <span class="font-medium">{{ $u->firstname }} {{ $u->name }}</span>
                            <span class="text-xs text-base-content/50 block">{{ $u->email }}</span>
                        </button>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</div>
