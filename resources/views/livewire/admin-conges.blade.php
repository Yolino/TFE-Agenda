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
                <td>{{ $demande->nb_jours . ($demande->nb_jours > 1 ? " jours" : " jour") }}</td>
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
                    <div class="bg-base-200 rounded-box p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="font-bold text-sm uppercase mb-1">Employé</p>
                                <p>{{ $demande->user->firstname }} {{ $demande->user->name }}</p>
                            </div>
                            <div>
                                <p class="font-bold text-sm uppercase mb-1">Type de congé</p>
                                <p>{{ $types[$demande->type] }}</p>
                            </div>
                            <div>
                                <p class="font-bold text-sm uppercase mb-1">Période</p>
                                <p>Du {{ $demande->formattedStartDate }} au {{ $demande->formattedEndDate }}</p>
                            </div>
                            <div>
                                <p class="font-bold text-sm uppercase mb-1">Nombre de jours</p>
                                <p>{{ $demande->nb_jours . ($demande->nb_jours > 1 ? " jours" : " jour") }}</p>
                            </div>
                            <div>
                                <p class="font-bold text-sm uppercase mb-1">Date de la demande</p>
                                <p>{{ \Carbon\Carbon::parse($demande->created_at)->translatedFormat('d M Y à H:i') }}</p>
                            </div>
                            <div>
                                <p class="font-bold text-sm uppercase mb-1">Statut</p>
                                <span class="badge {{ $statuts[$demande->status]['class'] }}">{{ $statuts[$demande->status]['label'] }}</span>
                            </div>
                            @if(in_array($demande->status, ['acceptee', 'refusee']) && $demande->decidedBy)
                            <div class="col-span-2">
                                <p class="font-bold text-sm uppercase mb-1">Décision</p>
                                <x-conge-decision :conge="$demande" />
                            </div>
                            @endif
                        </div>
                        <div class="flex justify-end mt-4 gap-2">
                            <a href="/mes-conges/pdf/{{ $demande->id }}" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="fa-duotone fa-file-pdf mr-1"></i> Voir le PDF
                            </a>
                            @if($demande->status === 'envoyee')
                                <button wire:click="accepter({{ $demande->id }})" class="btn btn-sm btn-success">
                                    <i class="fa-duotone fa-check mr-1"></i> Accepter
                                </button>
                                <button wire:click="refuser({{ $demande->id }})" class="btn btn-sm btn-error">
                                    <i class="fa-duotone fa-xmark mr-1"></i> Refuser
                                </button>
                            @endif
                        </div>
                    </div>
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
