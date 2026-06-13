@extends("app")
@section("title", "Mon profil")

@section("content")
@include("partials.nav")

<div class="p-4 max-w-6xl mx-auto space-y-6">

    @include('partials.flash')

    <div class="card-eg">
        <h1 class="text-3xl font-bold">Mon profil</h1>
    </div>

    <div class="card-eg">
        <div class="flex flex-col sm:flex-row gap-6 items-start">

            <div class="flex-shrink-0">
                <div class="w-24 h-24 rounded-full bg-accent relative overflow-hidden shadow-md">
                    <div class="absolute inset-0 flex items-center justify-center bg-black text-white text-sm bg-opacity-60 opacity-0 hover:opacity-100 transition-opacity duration-300 cursor-pointer">
                        <i class="fa-solid fa-pencil text-lg"></i>
                    </div>
                    <div class="flex items-center justify-center h-full font-bold text-2xl select-none">
                        {{ strtoupper(auth()->user()->firstname[0] . auth()->user()->name[0]) }}
                    </div>
                </div>
                <div class="text-center mt-2">
                    @php $profileUser = auth()->user(); @endphp
                    <span class="badge {{ $profileUser->is_directeur() ? 'badge-secondary' : ($profileUser->is_admin() ? 'badge-error' : 'badge-info') }} badge-sm">
                        {{ $profileUser->is_directeur() ? 'Direction' : ($profileUser->is_admin() ? 'Admin' : 'Utilisateur') }}
                    </span>
                </div>
            </div>

            <div class="flex-1">
                @livewire('edit-profile')
            </div>
        </div>
    </div>

    @if(auth()->user()->hasPersonalAgenda())
    <div class="card-eg">
        <h2 class="text-xl font-bold mb-1 flex items-center gap-2">
            <i class="fa-solid fa-calendar-week text-primary"></i>
            Mon horaire prédéfini
        </h2>
        <p class="text-sm text-gray-500 mb-5">
            Cet horaire sert de modèle de base pour remplir votre planning hebdomadaire.
        </p>

        <form action="{{ route('profile.updatePlanning') }}" method="post" id="planning-form">
            @csrf
            @method('POST')

            @php
            $weekDays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
            $noTimeStatuses = ['recup', 'conge', 'css', 'indisponible', 'neant'];
            @endphp

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 items-start">
                @foreach ($weekDays as $index => $day)
                @php
                    $dayData         = $planning->{$day} ?? '';
                    $times           = explode(',', $dayData);
                    $morning_start   = isset($times[0]) && $times[0] !== '' ? (explode('-', $times[0])[0] ?? '') : '';
                    $morning_end     = isset($times[0]) && $times[0] !== '' ? (explode('-', $times[0])[1] ?? '') : '';
                    $afternoon_start = isset($times[1]) && $times[1] !== '' ? (explode('-', $times[1])[0] ?? '') : '';
                    $afternoon_end   = isset($times[1]) && $times[1] !== '' ? (explode('-', $times[1])[1] ?? '') : '';
                    $status          = $planning->{$day . '_status'} ?? 'bureau';

                    $dayLabels  = ['lundi'=>'Lun','mardi'=>'Mar','mercredi'=>'Mer','jeudi'=>'Jeu','vendredi'=>'Ven','samedi'=>'Sam','dimanche'=>'Dim'];
                    $isWeekend  = in_array($day, ['samedi', 'dimanche']);
                    $showTimes  = !in_array($status, $noTimeStatuses);
                @endphp

                <div x-data="{ showTimes: {{ $showTimes ? 'true' : 'false' }} }"
                     class="rounded-xl border border-base-200 shadow-sm {{ $isWeekend ? 'bg-base-200' : 'bg-base-100' }} p-3 space-y-2">

                    <div class="text-center">
                        <p class="font-bold text-sm uppercase tracking-widest {{ $isWeekend ? 'text-gray-400' : '' }}">
                            {{ $dayLabels[$day] }}
                        </p>
                        <p class="text-xs text-gray-400 capitalize">{{ $day }}</p>
                    </div>

                    <select @change="showTimes = ['bureau', 'tele_travail'].includes($event.target.value)"
                            name="planning[{{ $day }}][status]"
                            class="w-full rounded-lg border border-gray-300 bg-white text-gray-800 text-sm py-1.5 px-2 cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="bureau"       @selected($status === 'bureau')>Bureau</option>
                        <option value="tele_travail" @selected($status === 'tele_travail')>Télétravail</option>
                        <option value="recup"        @selected($status === 'recup')>Récup</option>
                        <option value="conge"        @selected($status === 'conge')>Congé</option>
                        <option value="css"          @selected($status === 'css')>CSS</option>
                        <option value="indisponible" @selected($status === 'indisponible')>Indisponible</option>
                        <option value="neant"        @selected($status === 'neant')>---</option>
                    </select>

                    <div x-show="showTimes" class="space-y-2">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase mb-1">Matin</p>
                            <input type="time" name="planning[{{ $day }}][morning_start]" value="{{ $morning_start }}"
                                   class="w-full rounded border border-gray-300 text-sm py-1 px-1.5 mb-1" />
                            <input type="time" name="planning[{{ $day }}][morning_end]" value="{{ $morning_end }}"
                                   class="w-full rounded border border-gray-300 text-sm py-1 px-1.5" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase mb-1">Après-midi</p>
                            <input type="time" name="planning[{{ $day }}][afternoon_start]" value="{{ $afternoon_start }}"
                                   class="w-full rounded border border-gray-300 text-sm py-1 px-1.5 mb-1" />
                            <input type="time" name="planning[{{ $day }}][afternoon_end]" value="{{ $afternoon_end }}"
                                   class="w-full rounded border border-gray-300 text-sm py-1 px-1.5" />
                        </div>
                    </div>

                </div>
                @endforeach
            </div>

            <div class="mt-5">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                    Sauvegarder l'horaire
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection

@push("scripts")
<script>
    document.getElementById('planning-form')?.addEventListener('submit', function (event) {
        const workStatuses = ['bureau', 'tele_travail'];
        const days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        let errors = [];

        days.forEach(day => {
            const statusEl = document.querySelector(`select[name='planning[${day}][status]']`);
            if (!statusEl || !workStatuses.includes(statusEl.value)) return;

            const ms = document.querySelector(`input[name='planning[${day}][morning_start]']`)?.value;
            const me = document.querySelector(`input[name='planning[${day}][morning_end]']`)?.value;
            const as = document.querySelector(`input[name='planning[${day}][afternoon_start]']`)?.value;
            const ae = document.querySelector(`input[name='planning[${day}][afternoon_end]']`)?.value;

            if ((ms && !me) || (!ms && me)) {
                errors.push(`Heures du matin incomplètes pour le ${day}.`);
            }
            if ((as && !ae) || (!as && ae)) {
                errors.push(`Heures de l'après-midi incomplètes pour le ${day}.`);
            }
        });

        if (errors.length > 0) {
            event.preventDefault();
            Swal.fire({
                title: 'Erreur de saisie',
                html: errors.map(e => `<div>• ${e}</div>`).join(''),
                icon: 'error',
                confirmButtonText: 'Corriger'
            });
        }
    });
</script>
@endpush
