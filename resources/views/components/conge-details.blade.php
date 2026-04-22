@props(['conge'])

@php
    $types = [
        'recup'  => 'Récupération',
        'conge'  => 'Congé',
        'css'    => 'Congé sans solde',
        'visite' => 'Visite médicale',
        'autre'  => 'Autre',
    ];
    $statuts = [
        'en_cours' => ['label' => 'Brouillon',  'class' => 'badge-ghost'],
        'envoyee'  => ['label' => 'En attente', 'class' => 'badge-info'],
        'acceptee' => ['label' => 'Acceptée',   'class' => 'badge-success'],
        'refusee'  => ['label' => 'Refusée',    'class' => 'badge-error'],
    ];
    $formatJours = function ($n) {
        $n = (float) $n;
        $clean = rtrim(rtrim(number_format($n, 1, ',', ' '), '0'), ',');
        return $clean . ' ' . ($n > 1 ? 'jours' : 'jour');
    };
    $startFormatted = $conge->formattedStartDate ?? \Carbon\Carbon::parse($conge->start_date)->translatedFormat('d M Y');
    $endFormatted   = $conge->formattedEndDate   ?? \Carbon\Carbon::parse($conge->end_date)->translatedFormat('d M Y');
@endphp

<div class="bg-base-200 rounded-box p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="font-bold text-sm uppercase mb-1">Employé</p>
            <p>{{ $conge->user->firstname }} {{ $conge->user->name }}</p>
        </div>
        <div>
            <p class="font-bold text-sm uppercase mb-1">Type de congé</p>
            <p>{{ $types[$conge->type] ?? $conge->type }}</p>
        </div>
        <div>
            <p class="font-bold text-sm uppercase mb-1">Période</p>
            <p>Du {{ $startFormatted }} au {{ $endFormatted }}</p>
        </div>
        <div>
            <p class="font-bold text-sm uppercase mb-1">Nombre de jours</p>
            <p>
                {{ $formatJours($conge->nb_jours) }}
                @if(fmod((float) $conge->nb_jours, 1) !== 0.0)
                    <span class="badge badge-xs badge-outline ml-1">demi-journée</span>
                @endif
            </p>
        </div>
        <div>
            <p class="font-bold text-sm uppercase mb-1">Date de la demande</p>
            <p>{{ \Carbon\Carbon::parse($conge->created_at)->translatedFormat('d M Y à H:i') }}</p>
        </div>
        <div>
            <p class="font-bold text-sm uppercase mb-1">Statut</p>
            <span class="badge {{ $statuts[$conge->status]['class'] ?? 'badge-ghost' }}">
                {{ $statuts[$conge->status]['label'] ?? $conge->status }}
            </span>
        </div>
        @if(in_array($conge->status, ['acceptee', 'refusee']) && $conge->decidedBy)
        <div class="col-span-2">
            <p class="font-bold text-sm uppercase mb-1">Décision</p>
            <x-conge-decision :conge="$conge" />
        </div>
        @endif
    </div>

    @if(isset($actions))
        <div class="flex justify-end mt-4 gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
