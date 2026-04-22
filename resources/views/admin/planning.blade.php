@extends("app")
@section("title", "Planning des utilisateurs - Admin")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')

    <div class="card-eg">
        <h1 class="text-4xl font-medium">Planning Crocheux</h1>
    </div>

    <div class="card-eg mt-4 p-4">
        <div class="flex justify-between mb-6 items-center">
            @php
                $previousWeek = $selectedWeek - 1;
                $nextWeek = $selectedWeek + 1;
                $previousYear = $selectedYear;
                $nextYear = $selectedYear;

                if ($previousWeek < 1) { $previousWeek = 52; $previousYear--; }
                if ($nextWeek > 52)    { $nextWeek = 1;      $nextYear++;     }
            @endphp

            <button @click="window.location.href='?week={{ $previousWeek }}&year={{ $previousYear }}'" class="btn bg-blue-500 text-white px-4 py-2 rounded">
                <i class="fa-solid fa-arrow-left"></i> Semaine précédente
            </button>
            <h2 class="text-xl font-bold">Semaine {{ $selectedWeek }} - {{ $selectedYear }}</h2>
            <button @click="window.location.href='?week={{ $nextWeek }}&year={{ $nextYear }}'" class="btn bg-blue-500 text-white px-4 py-2 rounded">
                Semaine suivante <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <div class="flex justify-end mb-4">
            <a href="{{ route('planning.export', ['week' => $selectedWeek, 'year' => $selectedYear]) }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Télécharger en Excel
            </a>
        </div>

        @php
            $congeTypeLabels = [
                'recup' => 'Récup.',
                'conge' => 'Congé (VA)',
                'css' => 'CSS',
                'visite' => 'Visite méd.',
                'autre' => 'Autre',
            ];
            $maxEntries = $planningEntries->groupBy('date')->map->count()->max();
        @endphp

        <div class="grid grid-cols-6 gap-4">
            {{-- En-têtes des jours --}}
            @foreach($daysInWeek as $day)
            @php $isHoliday = isset($holidays[$day->toDateString()]); @endphp
            <div class="text-center font-bold p-2 border rounded {{ $isHoliday ? 'bg-teal-300 text-teal-900' : 'bg-gray-100' }}">
                {{ $day->translatedFormat('l d/m') }}
                @if($isHoliday)
                    <p class="text-[10px] font-normal italic">{{ $holidays[$day->toDateString()] }}</p>
                @endif
            </div>
            @endforeach

            {{-- Entrées --}}
            @for ($i = 0; $i < $maxEntries; $i++)
                @foreach($daysInWeek as $day)
                @php
                    $dateStr  = $day->toDateString();
                    $isHoliday = isset($holidays[$dateStr]);
                    $entries  = $planningEntries->filter(fn($e) => $e->date === $dateStr)->values();
                    $entry    = $entries[$i] ?? null;

                    if ($entry === null) continue;

                    $bgClasses = $isHoliday ? 'bg-teal-200 text-teal-900' : match([$entry->user->type ?? null, $entry->status != 'bureau']) {
                        ['I', false] => 'bg-orange-100',
                        ['I', true]  => 'bg-gray-400',
                        ['C', false] => 'bg-sky-200',
                        ['C', true]  => 'bg-gray-400',
                        ['B', false] => 'bg-lime-200',
                        ['B', true]  => 'bg-gray-400',
                        ['S', false] => 'bg-orange-400',
                        ['S', true]  => 'bg-gray-400',
                        default      => 'bg-white',
                    };

                    $textColorClasses = $isHoliday ? 'text-teal-900' : match([$entry->status ?? null]) {
                        ['recup']       => 'text-red-700',
                        ['indisponible']=> 'text-red-700',
                        ['maladie']     => 'text-pink-700',
                        ['tele_travail']=> 'text-green-700',
                        default         => 'text-gray-800',
                    };
                @endphp
                <div class="border p-2 rounded {{ $bgClasses }}">
                    <p class="text-sm text-center font-medium {{ $textColorClasses }}">{{ $entry->user->name }} {{ $entry->user->firstname }}</p>
                    @if($isHoliday)
                        <p class="font-bold text-center mt-2">JOUR FÉRIÉ</p>
                        <p class="text-xs text-center italic mt-1">{{ $holidays[$dateStr] }}</p>
                    @else
                        <p class="text-center mt-2">
                            <span class="font-semibold {{ $textColorClasses }}">
                                {{ $entry->status === 'tele_travail' ? 'Domicile' : ucfirst($entry->status) }}
                            </span>
                        </p>
                        @if($entry->demandeConge && $entry->demandeConge->status === 'acceptee')
                            <p class="text-xs text-center italic opacity-80 {{ $textColorClasses }}">
                                {{ $congeTypeLabels[$entry->demandeConge->type] ?? $entry->demandeConge->type }}
                            </p>
                        @endif
                        <ul class="text-xs text-center mt-2 {{ $textColorClasses }}">
                            @if($entry->start_time && $entry->end_time)
                                <li>{{ $entry->start_time }} à {{ $entry->end_time }}</li>
                            @endif
                            @if($entry->start_time_afternoon && $entry->end_time_afternoon)
                                <li>{{ $entry->start_time_afternoon }} à {{ $entry->end_time_afternoon }}</li>
                            @endif
                        </ul>
                    @endif
                </div>
                @endforeach
            @endfor
        </div>
    </div>
</div>
@endsection
