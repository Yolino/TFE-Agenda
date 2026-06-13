@props([
    'years' => collect(),
    'yearProp' => 'filterYear',
    'monthProp' => 'filterMonth',
])

@php
    $months = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
@endphp

<div class="flex flex-wrap gap-2 items-center">
    <span class="text-sm font-semibold">Période :</span>
    <select wire:model.live="{{ $yearProp }}" class="select select-bordered select-sm">
        <option value="">Toutes les années</option>
        @foreach($years as $y)
            <option value="{{ $y }}">{{ $y }}</option>
        @endforeach
    </select>
    <select wire:model.live="{{ $monthProp }}" class="select select-bordered select-sm">
        <option value="">Tous les mois</option>
        @foreach($months as $num => $label)
            <option value="{{ $num }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
