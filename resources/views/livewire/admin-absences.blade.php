<div>
    @php
        $formatJours = function ($n) {
            $n     = (float) $n;
            $isInt = floor($n) == $n;
            $value = $isInt ? (int) $n : rtrim(rtrim(number_format($n, 1, ',', ' '), '0'), ',');
            $label = $n > 1 ? 'jours' : 'jour';
            $class = match (true) {
                $n <= 1  => 'badge-success',
                $n <= 5  => 'badge-info',
                $n <= 14 => 'badge-warning',
                default  => 'badge-error',
            };
            return compact('value', 'label', 'class', 'isInt');
        };
    @endphp

    <div role="tablist" class="tabs tabs-boxed mb-4 w-fit">
        <button role="tab"
                wire:click="setTab('en_cours')"
                class="tab {{ $tab === 'en_cours' ? 'tab-active' : '' }}">
            Absences en cours
            @if($nbEnCours > 0)
                <span class="badge badge-sm badge-warning ml-2">{{ $nbEnCours }}</span>
            @endif
        </button>
        <button role="tab"
                wire:click="setTab('a_venir')"
                class="tab {{ $tab === 'a_venir' ? 'tab-active' : '' }}">
            À venir
            @if($nbAVenir > 0)
                <span class="badge badge-sm badge-info ml-2">{{ $nbAVenir }}</span>
            @endif
        </button>
        <button role="tab"
                wire:click="setTab('historique')"
                class="tab {{ $tab === 'historique' ? 'tab-active' : '' }}">
            Historique
        </button>
    </div>

    <div class="mb-4 flex flex-col gap-3">
        <div class="flex flex-wrap gap-2 items-center">
            <span class="text-sm font-semibold">Agence :</span>
            <x-agence-autocomplete
                :agences="$agences"
                :selected="$filterAgenceId"
                placeholder="Toutes les agences"
                nullable
                null-label="Toutes les agences"
                input-class="input input-bordered input-sm w-64"
                dropdown-class="w-64"
                @selected="$wire.filterByAgence($event.detail.value)" />
            @if($filterUserId !== null)
                <span class="text-xs text-base-content/50 italic">(le filtre agence est ignoré quand un utilisateur est sélectionné)</span>
            @endif
        </div>

        <div class="flex flex-wrap gap-x-6 gap-y-3 items-center">
            <x-filter-user
                :results="$userResults"
                :search="$userSearch"
                :selected-label="$filterUserLabel" />

            <x-filter-period :years="$years" />
        </div>
    </div>

    <div class="space-y-6">
        @forelse($absencesByAgence as $agenceLabel => $items)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h2 class="card-title text-base">
                        <i class="fa-duotone fa-building text-accent mr-1"></i>
                        {{ $agenceLabel }}
                        <span class="badge badge-ghost badge-sm">{{ $countsByAgence[$agenceLabel] ?? $items->count() }}</span>
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th class="hidden md:table-cell">Période</th>
                                    <th class="hidden sm:table-cell">Durée</th>
                                    <th class="text-right">Certificat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $absence)
                                    <tr>
                                        <td class="font-medium">
                                            {{ $absence->user->firstname }} {{ $absence->user->name }}
                                        </td>
                                        <td class="hidden md:table-cell">
                                            du {{ $absence->formattedStartDate }} au {{ $absence->formattedEndDate }}
                                        </td>
                                        <td class="hidden sm:table-cell">
                                            @php $j = $formatJours($absence->nb_jours); @endphp
                                            <div class="inline-flex items-center gap-1.5 rounded-full bg-base-200 px-2.5 py-1">
                                                <span class="inline-flex h-2 w-2 rounded-full
                                                    {{ str_replace('badge-', 'bg-', $j['class']) }}"></span>
                                                <span class="font-semibold text-sm leading-none">{{ $j['value'] }}</span>
                                                <span class="text-xs text-base-content/70 leading-none">{{ $j['label'] }}</span>
                                                @unless($j['isInt'])
                                                    <span class="tooltip" data-tip="Demi-journée">
                                                        <i class="fa-duotone fa-clock-half-past text-base-content/50 text-xs"></i>
                                                    </span>
                                                @endunless
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            @if($absence->certificat_medical)
                                                <a href="{{ route('justificatif-absence.certificat', $absence->id) }}"
                                                   target="_blank"
                                                   class="btn btn-xs btn-primary tooltip inline-flex items-center justify-center"
                                                   data-tip="Visualiser le certificat médical">
                                                    <i class="fa-duotone fa-file-medical mr-1"></i>
                                                    Voir
                                                </a>
                                            @else
                                                <span class="text-base-content/40 text-xs">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">
                <i class="fa-duotone fa-circle-info"></i>
                <span>
                    @switch($tab)
                        @case('en_cours') Aucun collaborateur n'est actuellement absent. @break
                        @case('a_venir')  Aucune absence planifiée à venir. @break
                        @default          Aucune absence dans l'historique pour ce filtre.
                    @endswitch
                </span>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $absences->links() }}
    </div>
</div>
