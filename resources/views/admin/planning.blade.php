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
        @if(auth()->user()->canEditGlobalPlanning())
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

            <button @click="window.location.href='?week={{ $previousWeek }}&year={{ $previousYear }}{{ $agenceQs }}'" class="btn btn-primary">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="hidden sm:inline ml-1">Semaine précédente</span>
            </button>
            <h2 class="text-base sm:text-xl font-bold text-center">Sem. {{ $selectedWeek }} — {{ $selectedYear }}</h2>
            <button @click="window.location.href='?week={{ $nextWeek }}&year={{ $nextYear }}{{ $agenceQs }}'" class="btn btn-primary">
                <span class="hidden sm:inline mr-1">Semaine suivante</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <div class="flex justify-end mb-4"
             @student-assignment-changed.window="window.location.reload()">
            <livewire:send-planning-email :week="$selectedWeek" :year="$selectedYear" :agenceId="$currentAgence?->id" />
            @if(auth()->user()->canEditGlobalPlanning() && $currentAgence)
                <livewire:student-assignment-manager :week="$selectedWeek" :year="$selectedYear" :agenceId="$currentAgence->id" />
            @endif

            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button"
                     class="btn btn-primary tooltip tooltip-left inline-flex items-center gap-1"
                     data-tip="Options & exports">
                    <i class="fa-solid fa-sliders leading-none hidden md:inline"></i>
                </div>

                <ul tabindex="0" class="dropdown-content menu menu-md bg-base-100 rounded-box shadow-lg z-10 w-60 p-2 mt-1 border border-base-200">
                    <li>
                        <a href="{{ route('planning.export.pdf', $exportParams) }}" class="flex items-center gap-3">
                            <i class="fa-solid fa-file-pdf w-5 text-red-500"></i>
                            <span>Télécharger en PDF</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('planning.export', $exportParams) }}" class="flex items-center gap-3">
                            <i class="fa-solid fa-file-excel w-5 text-green-600"></i>
                            <span>Télécharger en Excel</span>
                        </a>
                    </li>
                    <li>
                        <button type="button"
                                @click="$dispatch('open-planning-email')"
                                class="flex items-center gap-3">
                            <i class="fa-solid fa-paper-plane w-5 text-blue-500"></i>
                            <span>Envoyer par email</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        @php
            $congeTypeLabels = [
                'recup' => 'Récup.',
                'conge' => 'Congé (VA)',
                'css' => 'CSS',
                'visite' => 'Visite méd.',
                'autre' => 'Autre',
            ];

            $typeOrder = ['D' => 1, 'S' => 2, 'B' => 3, 'C' => 4, 'I' => 5, 'M' => 6, 'O' => 7, 'G' => 8, 'N' => 9, 'F' => 10, 'V' => 11];

            $activeUsers = $users->where('actif', true)
                ->sortBy([
                    fn($a, $b) => ($typeOrder[$a->departements->first()?->letter] ?? 99) <=> ($typeOrder[$b->departements->first()?->letter] ?? 99),
                    fn($a, $b) => strcmp($a->name, $b->name),
                ])
                ->groupBy(fn($u) => $u->departements->first()?->letter ?? '?')
                ->sortBy(fn($group, $type) => $typeOrder[$type] ?? 99);

            $deptLabels = $users
                ->mapWithKeys(fn($u) => [$u->departements->first()?->letter ?? '?' => $u->departements->first()?->nom ?? '—']);

            $entriesForJs = $planningEntries->map(fn($e) => [
                'id'                    => $e->id,
                'user_id'               => $e->user_id,
                'date'                  => $e->date instanceof \Carbon\Carbon ? $e->date->toDateString() : (string) $e->date,
                'status'                => $e->status,
                'demande_conge_status'  => $e->demandeConge?->status,
                'start_time'            => $e->start_time,
                'end_time'              => $e->end_time,
                'start_time_afternoon'  => $e->start_time_afternoon,
                'end_time_afternoon'    => $e->end_time_afternoon,
                'custom'                => $e->custom,
            ])->values();

            $userNamesForJs = $users->mapWithKeys(fn($u) => [$u->id => trim($u->name . ' ' . $u->firstname)]);
        @endphp

        <div x-data="adminPlanningGrid({
            entries: {{ json_encode($entriesForJs) }},
            userNames: {{ json_encode($userNamesForJs) }},
            week: {{ $selectedWeek }},
            year: {{ $selectedYear }}
        })">
        <div class="overflow-x-auto -mx-4 px-4">
        <div class="grid gap-1 min-w-[640px]" style="grid-template-columns: minmax(120px,160px) repeat(6, 1fr);">
            <div class="text-center font-bold p-2 border rounded bg-gray-100"></div>

            @foreach($daysInWeek as $day)
            @php $isHoliday = isset($holidays[$day->toDateString()]); @endphp
            <div class="text-center font-bold p-2 border rounded {{ $isHoliday ? 'bg-teal-300 text-teal-900' : 'bg-gray-100' }}">
                {{ $day->translatedFormat('l d/m') }}
                @if($isHoliday)
                    <p class="text-[10px] font-normal italic">{{ $holidays[$day->toDateString()] }}</p>
                @endif
            </div>
            @endforeach

            @foreach($activeUsers as $type => $usersInGroup)
                @php
                    $deptBg = match($type) {
                        'D' => 'bg-violet-100 text-violet-800',
                        'S' => 'bg-orange-100 text-orange-800',
                        'B' => 'bg-lime-100 text-lime-800',
                        'C' => 'bg-sky-100 text-sky-800',
                        'I' => 'bg-orange-50 text-orange-700',
                        'M' => 'bg-yellow-100 text-yellow-800',
                        'O' => 'bg-amber-100 text-amber-800',
                        'G' => 'bg-slate-100 text-slate-700',
                        'N' => 'bg-emerald-100 text-emerald-800',
                        'F' => 'bg-rose-100 text-rose-800',
                        'V' => 'bg-indigo-100 text-indigo-800',
                        default => 'bg-gray-100 text-gray-700',
                    };
                @endphp
                <div class="col-span-7 {{ $deptBg }} font-bold text-sm px-3 py-1 border rounded mt-2 flex items-center justify-between">
                    <span>{{ $deptLabels[$type] ?? $type }}</span>
                    @if(auth()->user()->canEditGlobalPlanning())
                        <button type="button"
                                @click="$dispatch('open-student-assignment', { letter: '{{ $type }}' })"
                                class="btn btn-xs btn-ghost tooltip tooltip-left"
                                data-tip="Assigner un étudiant">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    @endif
                </div>

                @foreach($usersInGroup->sortBy('name') as $user)
                    @php $isStudent = str_contains((string) ($user->acces_level ?? ''), 'ET'); @endphp
                    <div class="border p-2 rounded bg-gray-50 flex items-center gap-1.5">
                        @if($isStudent)
                            <i class="fa-solid fa-user-graduate text-indigo-500 text-xs tooltip" data-tip="Étudiant" title="Étudiant"></i>
                        @endif
                        <span class="text-sm font-medium">{{ $user->name }} {{ $user->firstname }}</span>
                    </div>

                    @foreach($daysInWeek as $day)
                    @php
                        $dateStr   = $day->toDateString();
                        $isHoliday = isset($holidays[$dateStr]);
                        $entry     = $planningEntries->where('user_id', $user->id)->where('date', $dateStr)->first();

                        if ($isHoliday) {
                            $bgClasses = 'bg-teal-200';
                            $textColorClasses = 'text-teal-900';
                            $customBgStyle = '';
                        } elseif ($entry) {
                            $isAbsent = !in_array($entry->status, ['bureau', 'tele_travail', 'custom']);
                            $bgClasses = $entry->status === 'custom'
                                ? ''
                                : match([$user->departements->first()?->letter, $isAbsent]) {
                                    ['D', false] => 'bg-violet-200',
                                    ['D', true]  => 'bg-gray-400',
                                    ['S', false] => 'bg-orange-400',
                                    ['S', true]  => 'bg-gray-400',
                                    ['B', false] => 'bg-lime-200',
                                    ['B', true]  => 'bg-gray-400',
                                    ['C', false] => 'bg-sky-200',
                                    ['C', true]  => 'bg-gray-400',
                                    ['I', false] => 'bg-orange-100',
                                    ['I', true]  => 'bg-gray-400',
                                    ['M', false] => 'bg-yellow-200',
                                    ['M', true]  => 'bg-gray-400',
                                    ['O', false] => 'bg-amber-200',
                                    ['O', true]  => 'bg-gray-400',
                                    ['G', false] => 'bg-slate-300',
                                    ['G', true]  => 'bg-gray-400',
                                    ['N', false] => 'bg-emerald-200',
                                    ['N', true]  => 'bg-gray-400',
                                    ['F', false] => 'bg-rose-200',
                                    ['F', true]  => 'bg-gray-400',
                                    ['V', false] => 'bg-indigo-200',
                                    ['V', true]  => 'bg-gray-400',
                                    default      => 'bg-white',
                                };
                            $customBgStyle = '';
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
                            $customBgStyle = '';
                        }
                    @endphp

                    <div class="border p-2 rounded {{ $bgClasses }} {{ $isHoliday ? '' : 'cursor-pointer hover:ring-2 hover:ring-primary/40 transition' }}"
                         @if($customBgStyle) style="{{ $customBgStyle }}" @endif
                         @if(!$isHoliday)
                            @click="openCell({ userId: {{ $user->id }}, date: '{{ $dateStr }}' })"
                         @endif>
                        @if($isHoliday)
                            <p class="font-bold text-center text-teal-900">JOUR FÉRIÉ</p>
                            <p class="text-xs text-center italic mt-1 text-teal-900">{{ $holidays[$dateStr] }}</p>
                        @elseif($entry)
                            <p class="text-center mt-1">
                                <span class="font-semibold text-sm {{ $textColorClasses }}">
                                    @if($entry->status === 'tele_travail') Domicile
                                    @elseif($entry->status === 'custom') {{ $entry->custom ?? 'Personnalisé' }}
                                    @else {{ ucfirst($entry->status) }}
                                    @endif
                                </span>
                            </p>
                            @if($entry->demandeConge && $entry->demandeConge->status === 'acceptee')
                                <p class="text-xs text-center italic opacity-80 {{ $textColorClasses }}">
                                    {{ $congeTypeLabels[$entry->demandeConge->type] ?? $entry->demandeConge->type }}
                                </p>
                            @endif
                            @unless(in_array($entry->status, ['indisponible', 'recup', 'conge', 'maladie']))
                            <ul class="text-xs text-center mt-1 {{ $textColorClasses }}">
                                @if($entry->start_time && $entry->end_time)
                                    <li>{{ $entry->start_time }} à {{ $entry->end_time }}</li>
                                @endif
                                @if($entry->start_time_afternoon && $entry->end_time_afternoon)
                                    <li>{{ $entry->start_time_afternoon }} à {{ $entry->end_time_afternoon }}</li>
                                @endif
                            </ul>
                            @endunless
                        @else
                            <p class="text-xs text-center text-gray-300 italic">—</p>
                        @endif
                    </div>
                    @endforeach
                @endforeach
            @endforeach
        </div>
        </div>

        @include('partials.planning-day-modal')
        </div>
    </div>
</div>

@push('scripts')
<script>
    function adminPlanningGrid(opts) {
        return Object.assign({}, dayModalMixin({
            isAdmin: true,
            defaults: {
                start: '09:00', end: '12:30',
                start_afternoon: '13:00', end_afternoon: '16:30',
            },
        }), {
            allEntries: opts.entries || [],
            userNames: opts.userNames || {},
            week: opts.week,
            year: opts.year,

            findEntry(userId, date) {
                return this.allEntries.find(e => e.user_id == userId && e.date === date) || null;
            },

            openCell({ userId, date }) {
                const entry = this.findEntry(userId, date);
                this.openDayModalFor({
                    userId: userId,
                    userName: this.userNames[userId] || '',
                    date: date,
                    entry: entry,
                });
            },

            afterSubmit() { window.location.reload(); },
            afterDelete() { window.location.reload(); },
        });
    }
</script>
@endpush
@endsection
