@extends("app")
@section("title", "Planning des utilisateurs - Admin")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')

    <div class="card-eg flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h1 class="text-2xl md:text-4xl font-medium">
            Planning {{ $currentAgence?->display_name ?? '— aucune agence' }}
        </h1>
        @if(auth()->user()->is_admin())
            <livewire:agence-autocomplete :week="$selectedWeek" :year="$selectedYear" />
        @endif
    </div>

    <div class="card-eg mt-4 p-4">
        <div class="flex justify-between mb-6 items-center">
            @php
                $previousWeek = $selectedWeek - 1;
                $nextWeek = $selectedWeek + 1;
                $previousYear = $selectedYear;
                $nextYear = $selectedYear;

                $weeksInCurrentYear  = (int) \Carbon\Carbon::create($selectedYear, 12, 28)->format('W');
                $weeksInPreviousYear = (int) \Carbon\Carbon::create($selectedYear - 1, 12, 28)->format('W');

                if ($previousWeek < 1)                   { $previousWeek = $weeksInPreviousYear; $previousYear--; }
                if ($nextWeek > $weeksInCurrentYear)     { $nextWeek = 1;                        $nextYear++;     }
            @endphp

            @php
                $agenceQs = $currentAgence ? '&agence_id=' . $currentAgence->id : '';
                $exportParams = ['week' => $selectedWeek, 'year' => $selectedYear];
                if ($currentAgence) { $exportParams['agence_id'] = $currentAgence->id; }
            @endphp

            <button @click="window.location.href='?week={{ $previousWeek }}&year={{ $previousYear }}{{ $agenceQs }}'" class="btn bg-blue-500 text-white px-3 py-2 rounded">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="hidden sm:inline ml-1">Semaine précédente</span>
            </button>
            <h2 class="text-base sm:text-xl font-bold text-center">Sem. {{ $selectedWeek }} — {{ $selectedYear }}</h2>
            <button @click="window.location.href='?week={{ $nextWeek }}&year={{ $nextYear }}{{ $agenceQs }}'" class="btn bg-blue-500 text-white px-3 py-2 rounded">
                <span class="hidden sm:inline mr-1">Semaine suivante</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <div class="flex flex-wrap justify-end mb-4 gap-2">
            <a href="{{ route('planning.export.pdf', $exportParams) }}" class="btn bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 text-sm">
                <i class="fa-solid fa-file-pdf mr-1"></i>
                <span class="hidden sm:inline">Télécharger en </span>PDF
            </a>
            <a href="{{ route('planning.export', $exportParams) }}" class="btn bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600 text-sm">
                <i class="fa-solid fa-file-excel mr-1"></i>
                <span class="hidden sm:inline">Télécharger en </span>Excel
            </a>
            <livewire:send-planning-email :week="$selectedWeek" :year="$selectedYear" :agenceId="$currentAgence?->id" />
        </div>

        @php
            $congeTypeLabels = [
                'recup' => 'Récup.',
                'conge' => 'Congé (VA)',
                'css' => 'CSS',
                'visite' => 'Visite méd.',
                'autre' => 'Autre',
            ];

            $typeOrder = ['B' => 1, 'S' => 2, 'C' => 3, 'I' => 4];

            $activeUsers = $users->where('actif', true)
                ->sortBy([
                    fn($a, $b) => ($typeOrder[$a->departements->first()?->letter] ?? 99) <=> ($typeOrder[$b->departements->first()?->letter] ?? 99),
                    fn($a, $b) => strcmp($a->name, $b->name),
                ])
                ->groupBy(fn($u) => $u->departements->first()?->letter ?? '?')
                ->sortBy(fn($group, $type) => $typeOrder[$type] ?? 99);

            $deptLabels = $users
                ->mapWithKeys(fn($u) => [$u->departements->first()?->letter ?? '?' => $u->departements->first()?->nom ?? '—']);
        @endphp

        <div class="overflow-x-auto -mx-4 px-4">
        <div class="grid gap-1 min-w-[640px]" style="grid-template-columns: minmax(120px,160px) repeat(6, 1fr);">
            {{-- En-tête : colonne nom --}}
            <div class="text-center font-bold p-2 border rounded bg-gray-100"></div>

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

            {{-- Groupes par département --}}
            @foreach($activeUsers as $type => $usersInGroup)
                {{-- Ligne de séparation département --}}
                @php
                    $deptBg = match($type) {
                        'B' => 'bg-lime-100 text-lime-800',
                        'S' => 'bg-orange-100 text-orange-800',
                        'C' => 'bg-sky-100 text-sky-800',
                        'I' => 'bg-orange-50 text-orange-700',
                        default => 'bg-gray-100 text-gray-700',
                    };
                @endphp
                <div class="col-span-7 {{ $deptBg }} font-bold text-sm px-3 py-1 border rounded mt-2">
                    {{ $deptLabels[$type] ?? $type }}
                </div>

                {{-- Une ligne par utilisateur du groupe --}}
                @foreach($usersInGroup->sortBy('name') as $user)
                    {{-- Colonne nom --}}
                    <div class="border p-2 rounded bg-gray-50 flex items-center">
                        <span class="text-sm font-medium">{{ $user->name }} {{ $user->firstname }}</span>
                    </div>

                    {{-- Une cellule par jour --}}
                    @foreach($daysInWeek as $day)
                    @php
                        $dateStr   = $day->toDateString();
                        $isHoliday = isset($holidays[$dateStr]);
                        $entry     = $planningEntries->where('user_id', $user->id)->where('date', $dateStr)->first();

                        if ($isHoliday) {
                            $bgClasses = 'bg-teal-200';
                            $textColorClasses = 'text-teal-900';
                        } elseif ($entry) {
                            $bgClasses = match([$user->departements->first()?->letter, $entry->status != 'bureau']) {
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
                            $textColorClasses = match($entry->status ?? null) {
                                'recup'        => 'text-red-700',
                                'indisponible' => 'text-red-700',
                                'maladie'      => 'text-pink-700',
                                'tele_travail' => 'text-green-700',
                                default        => 'text-gray-800',
                            };
                        } else {
                            $bgClasses = 'bg-white';
                            $textColorClasses = 'text-gray-400';
                        }
                    @endphp

                    <div class="border p-2 rounded {{ $bgClasses }}">
                        @if($isHoliday)
                            <p class="font-bold text-center text-teal-900">JOUR FÉRIÉ</p>
                            <p class="text-xs text-center italic mt-1 text-teal-900">{{ $holidays[$dateStr] }}</p>
                        @elseif($entry)
                            <p class="text-center mt-1">
                                <span class="font-semibold text-sm {{ $textColorClasses }}">
                                    {{ $entry->status === 'tele_travail' ? 'Domicile' : ucfirst($entry->status) }}
                                </span>
                            </p>
                            @if($entry->demandeConge && $entry->demandeConge->status === 'acceptee')
                                <p class="text-xs text-center italic opacity-80 {{ $textColorClasses }}">
                                    {{ $congeTypeLabels[$entry->demandeConge->type] ?? $entry->demandeConge->type }}
                                </p>
                            @endif
                            <ul class="text-xs text-center mt-1 {{ $textColorClasses }}">
                                @if($entry->start_time && $entry->end_time)
                                    <li>{{ $entry->start_time }} à {{ $entry->end_time }}</li>
                                @endif
                                @if($entry->start_time_afternoon && $entry->end_time_afternoon)
                                    <li>{{ $entry->start_time_afternoon }} à {{ $entry->end_time_afternoon }}</li>
                                @endif
                            </ul>
                        @else
                            <p class="text-xs text-center text-gray-300 italic">—</p>
                        @endif
                    </div>
                    @endforeach
                @endforeach
            @endforeach
        </div>
        </div>{{-- /overflow-x-auto --}}
    </div>
</div>
@endsection
