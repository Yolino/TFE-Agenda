<div>
    @php
    $types = [
        'recup' => 'Récupération',
        'conge' => 'Congé',
        'css' => 'Congé sans solde',
        'visite' => 'Visite médicale',
        'autre' => 'Autre'
    ];
    $statuts = [
        'en_cours' => ['label' => 'Brouillon', 'class' => 'badge-ghost'],
        'envoyee' => ['label' => 'En attente', 'class' => 'badge-info'],
        'acceptee' => ['label' => 'Acceptée', 'class' => 'badge-success'],
        'refusee' => ['label' => 'Refusée', 'class' => 'badge-error'],
    ];

    $formatJours = function ($n) {
        $n = (float) $n;
        $clean = rtrim(rtrim(number_format($n, 1, ',', ' '), '0'), ',');
        return $clean . ' ' . ($n > 1 ? 'jours' : 'jour');
    };
    @endphp

    <!-- Filtres -->
    <div class="flex gap-2 mb-4">
        <button wire:click="$set('filter', 'envoyee')" class="btn btn-sm {{ $filter === 'envoyee' ? 'btn-primary' : 'btn-ghost' }}">
            En attente
            @if($nbEnAttente > 0)
                <span class="badge badge-sm badge-warning">{{ $nbEnAttente }}</span>
            @endif
        </button>
        <button wire:click="$set('filter', 'acceptee')" class="btn btn-sm {{ $filter === 'acceptee' ? 'btn-primary' : 'btn-ghost' }}">Acceptées</button>
        <button wire:click="$set('filter', 'refusee')" class="btn btn-sm {{ $filter === 'refusee' ? 'btn-primary' : 'btn-ghost' }}">Refusées</button>
        <button wire:click="$set('filter', 'toutes')" class="btn btn-sm {{ $filter === 'toutes' ? 'btn-primary' : 'btn-ghost' }}">Toutes</button>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th></th>
                <th>Employé</th>
                <th>Type</th>
                <th>Date</th>
                <th>Nb jours</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($demandes as $demande)
            <tr class="hover cursor-pointer" wire:click="selectConge({{ $demande->id }})">
                <th>
                    <span class="tooltip tooltip-right cursor-pointer" data-tip="Visualiser la demande" onclick="event.stopPropagation(); window.open('/mes-conges/pdf/{{ $demande->id }}', '_blank');">
                        <i class="fa-duotone fa-eye"></i>
                    </span>
                </th>
                <td class="font-bold">{{ $demande->user->firstname }} {{ $demande->user->name }}</td>
                <td>{{ $types[$demande->type] }}</td>
                <td>du {{ $demande->formattedStartDate }} au {{ $demande->formattedEndDate }}</td>
                <td>
                    {{ $formatJours($demande->nb_jours) }}
                    @if(fmod((float) $demande->nb_jours, 1) !== 0.0)
                        <span class="badge badge-xs badge-outline ml-1">½</span>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $statuts[$demande->status]['class'] }}">{{ $statuts[$demande->status]['label'] }}</span>
                    <div class="mt-1"><x-conge-decision :conge="$demande" /></div>
                </td>
                <td>
                    @if($demande->status === 'envoyee')
                        <button wire:click.stop="accepter({{ $demande->id }})" class="btn btn-sm btn-success tooltip" data-tip="Accepter">
                            <i class="fa-duotone fa-check"></i>
                        </button>
                        <button wire:click.stop="refuser({{ $demande->id }})" class="btn btn-sm btn-error ml-1 tooltip" data-tip="Refuser">
                            <i class="fa-duotone fa-xmark"></i>
                        </button>
                    @endif
                </td>
            </tr>
            @if($selectedConge === $demande->id)
            <tr>
                <td colspan="7">
                    <x-conge-details :conge="$demande">
                        @if($demande->status === 'envoyee')
                            <x-slot:actions>
                                <button wire:click="accepter({{ $demande->id }})" class="btn btn-sm btn-success">
                                    <i class="fa-duotone fa-check mr-1"></i> Accepter
                                </button>
                                <button wire:click="refuser({{ $demande->id }})" class="btn btn-sm btn-error">
                                    <i class="fa-duotone fa-xmark mr-1"></i> Refuser
                                </button>
                            </x-slot:actions>
                        @endif
                    </x-conge-details>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="7" class="text-center text-gray-500">Aucune demande de congé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
