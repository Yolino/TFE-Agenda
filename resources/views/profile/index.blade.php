@extends("app")
@section("title", "Mon profile - E-Gestione")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-4xl font-medium">Mon profile</h1>
    </div>
    <div class="card-eg">
        <div class="flex gap-4">
            <div class=" mr-5">
                <div class="w-28 h-28 rounded-full bg-accent relative overflow-hidden">
                    <!-- Photo de profil  -->
                    <div class="absolute inset-0 flex items-center justify-center bg-black text-white text-xl bg-opacity-60 opacity-0 hover:opacity-100 transition-opacity duration-300 cursor-pointer">
                        <i class="fa-solid fa-pencil"></i>
                    </div>
                    <div class="flex items-center justify-center h-full font-bold">
                        {{ auth()->user()->firstname[0] . auth()->user()->name[0] }}
                        <!-- <img src="/images/stock/photo-1534528741775-53994a69daeb.jpg" /> -->
                    </div>
                </div>
            </div>
            <div class="w-4/5">
                <form action="" method="post" enctype="multipart/form-data">
                    @method('PATCH')
                    @csrf
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col">
                            <label for="firstname" class="uppercase text-sm font-bold mb-2">Prénom</label>
                            <input type="text" placeholder="" id="firstname" class="input w-full max-w-md" name="firstname" value="{{ auth()->user()->firstname }}" />
                        </div>
                        <div class="flex flex-col">
                            <label for="name" class="uppercase text-sm font-bold mb-2">Nom</label>
                            <input type="text" placeholder="" id="name" class="input w-full max-w-md" name="name" value="{{ auth()->user()->name }}" />
                        </div>
                        <div class="flex flex-col">
                            <label for="email" class="uppercase text-sm font-bold mb-2">Email</label>
                            <input type="email" placeholder="" id="email" class="input w-full max-w-md" name="email" value="{{ auth()->user()->email }}" />
                        </div>
                        <div class="flex flex-col">
                            <label for="password" class="uppercase text-sm font-bold mb-2">Mot de passe</label>
                            <input type="password" placeholder="" id="password" class="input w-full max-w-md" name="password" value="" />
                        </div>
                        <div class="flex flex-col">
                            <button class="btn btn-primary w-full max-w-md">Sauvegarder</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <form action="{{ route('profile.updatePlanning') }}" method="post" class="mt-10">
            <div class="grid grid-cols-7 gap-4 mt-5" x-data="{ selectedType: Array(7).fill('bureau') }">
                @csrf
                @method('POST')
                @php
                $weekDays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                @endphp
                @foreach ($weekDays as $index => $day)
                @php
                $dayData = $planning->{$day} ?? '';
                $times = explode(',', $dayData);

                $morning_start = isset($times[0]) && !empty($times[0]) ? explode('-', $times[0])[0] : '';
                $morning_end = isset($times[0]) && !empty($times[0]) ? explode('-', $times[0])[1] : '';
                $afternoon_start = isset($times[1]) && !empty($times[1]) ? explode('-', $times[1])[0] : '';
                $afternoon_end = isset($times[1]) && !empty($times[1]) ? explode('-', $times[1])[1] : '';

                $status = $planning->{$day . '_status'} ?? 'bureau';
                @endphp

                <div class="">
                    <div class="mb-2 text-center">
                        <strong>{{ ucfirst($day) }}</strong>
                    </div>
                    <div class="mb-2">
                        <select id="{{ $day }}_type" class="input w-full" onchange="toggleTimeInputs('{{ $day }}')" name="planning[{{ $day }}][status]">
                            <option value="bureau" {{ $status == 'bureau' ? 'selected' : '' }}>Bureau</option>
                            <option value="tele_travail" {{ $status == 'tele_travail' ? 'selected' : '' }}>Télétravail</option>
                            <option value="recup" {{ $status == 'recup' ? 'selected' : '' }}>Récup</option>
                            <option value="conge" {{ $status == 'conge' ? 'selected' : '' }}>Congé</option>
                            <option value="css" {{ $status == 'css' ? 'selected' : '' }}>CSS</option>
                            <option value="indisponible" {{ $status == 'indisponible' ? 'selected' : '' }}>Indisponible</option>
                            <option value="neant" {{ $status == 'neant' ? 'selected' : '' }}>---</option>
                        </select>
                    </div>
                    <div id="{{ $day }}_time_inputs" class="mb-2">
                        <label class="mt-3 mb-1 inline-block">Matin :</label>
                        <input type="time" class="input w-full mb-1" name="planning[{{ $day }}][morning_start]" value="{{ $morning_start }}" />
                        <input type="time" class="input w-full" name="planning[{{ $day }}][morning_end]" value="{{ $morning_end }}" />
                        <label class="mt-3 mb-1 inline-block">Après-midi :</label>
                        <input type="time" class="input w-full mb-1" name="planning[{{ $day }}][afternoon_start]" value="{{ $afternoon_start }}" />
                        <input type="time" class="input w-full" name="planning[{{ $day }}][afternoon_end]" value="{{ $afternoon_end }}" />
                    </div>
                </div>
                @endforeach

            </div>
            <div class="mt-4">
                <button class="btn btn-primary w-full max-w-md">Sauvegarder le Planning</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push("scripts")
@vite("resources/js/profile.js")
<script>
    function toggleTimeInputs(day) {
        const typeElement = document.getElementById(day + '_type');
        const timeInputsDiv = document.getElementById(day + '_time_inputs');

        if (typeElement) {
            const type = typeElement.value;
            if (type === 'recup' || type === 'conge' || type === 'css' || type === 'indisponible') {
                timeInputsDiv.style.display = 'none';
            } else {
                timeInputsDiv.style.display = 'block';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'].forEach(day => {
            toggleTimeInputs(day);
        });

        const planningForm = document.querySelector("form[action='{{ route('profile.updatePlanning') }}']");
        if (planningForm) {
            planningForm.addEventListener('submit', function(event) {
                let isValid = true;
                let errorMessage = '';

                ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'].forEach(day => {
                    const morningStart = document.querySelector(`input[name='planning[${day}][morning_start]']`);
                    const morningEnd = document.querySelector(`input[name='planning[${day}][morning_end]']`);
                    const afternoonStart = document.querySelector(`input[name='planning[${day}][afternoon_start]']`);
                    const afternoonEnd = document.querySelector(`input[name='planning[${day}][afternoon_end]']`);

                    // Vérifiez les champs du matin
                    if ((morningStart.value && !morningEnd.value) || (!morningStart.value && morningEnd.value)) {
                        isValid = false;
                        errorMessage += `Veuillez remplir correctement les heures du matin pour le ${day}.\n`;
                    }

                    // Vérifiez les champs de l'après-midi
                    if ((afternoonStart.value && !afternoonEnd.value) || (!afternoonStart.value && afternoonEnd.value)) {
                        isValid = false;
                        errorMessage += `Veuillez remplir correctement les heures de l'après-midi pour le ${day}.\n`;
                    }
                });

                if (!isValid) {
                    event.preventDefault();
                    Swal.fire({
                        title: 'Erreur',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
</script>
@endpush