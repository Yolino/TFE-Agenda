@extends("app")
@section("title", "Mon planning - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')

    <div class="card-eg">
        <h1 class="text-4xl font-medium">Mon planning</h1>
    </div>

    <div x-data='calendar({{ auth()->id() }}, @json($userEntries), @json($currentYear), @json($currentMonth), @json($startTime), @json($endTime), @json($startTimeAfternoon), @json($endTimeAfternoon))' class="card-eg">
        <div class="flex justify-between mb-6 items-center">
            <button @click="decrementMonth" :disabled="isDecrementDisabled()" class="btn">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <select x-model="selectedMonthYear" @change="updateCalendar" class="select">
                <template x-for="(monthYear, monthYearValue) in monthYearOptions" :key="monthYearValue">
                    <option x-bind:value="monthYearValue" x-text="monthYear"></option>
                </template>
            </select>
            <button @click="incrementMonth" :disabled="isIncrementDisabled()" class="btn">
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
        <div class="grid grid-cols-7 gap-4">
            @foreach($weekDays as $day)
            <div class="font-bold text-center border-b-2">
                {{ $day }}
            </div>
            @endforeach

            @for ($i = 1; $i <= 35; $i++) <div class="border aspect-square flex relative group" :class="{'bg-error': isDayFilled({{ $i }}).holiday, 'hover:bg-secondary hover:text-white cursor-pointer': daysInMonthArray[{{ $i - 1 }}] && !isDayFilled({{ $i }}).holiday, 'bg-success': isDayFilled({{ $i }}).filled && !isDayFilled({{ $i }}).holiday}" @click="!isDayFilled({{ $i }}).holiday && openDayModal({{ $i }})">
                @php
                $weekNumber = intdiv($i + 6, 7); // Ajoutez 6 pour que le calcul commence correctement à la première semaine
                @endphp
                <span x-text="daysInMonthArray[{{ $i - 1 }}]" class="text-[7px] xl:text-xs font-bold p-3 absolute top-0 left-0"></span>
                @if ($i % 7===1) <button @click.stop="fillWeekAutomatically({{ $weekNumber }})" class="btn btn-xs text-[7px] xl:text-xs btn-ghost absolute top-2 right-2 z-10 opacity-25 hover:opacity-100 tooltip" data-tip="Remplir automatiquement">auto <i class="fa-duotone fa-arrow-right"></i></button>@endif
                <template x-if="isDayFilled({{ $i }}).holiday">
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p class="text-center font-bold">FÉRIÉ</p>
                        <p class="text-xs text-center" x-text="isDayFilled({{ $i }}).holidayName"></p>
                    </div>
                </template>

                <template x-if="isDayFilled({{ $i }}).filled">
                    <div class="absolute inset-0 flex flex-col items-center justify-center" :class="{
                        'bg-blue-200/[0.3]': isDayFilled({{ $i }}).entry.status === 'bureau',
                        'bg-green-500/[0.4]': isDayFilled({{ $i }}).entry.status === 'tele_travail',
                        'bg-yellow-300/[0.9]': isDayFilled({{ $i }}).entry.status === 'conge',
                        'bg-red-400/[0.9]': isDayFilled({{ $i }}).entry.status === 'indisponible',
                        'bg-purple-400/[0.9]': isDayFilled({{ $i }}).entry.status === 'recup',
                        'bg-orange-400/[0.8]': isDayFilled({{ $i }}).entry.status === 'css'
                    }">
                        <p x-text="formatStatus(isDayFilled({{ $i }}).entry.status)" class="text-[10px] xl:text-base text-center font-bold"></p>
                        <p x-show="isDayFilled({{ $i }}).entry.status === 'tele_travail' || isDayFilled({{ $i }}).entry.status === 'bureau'" class="text-xs text-center">
                            <template x-if="isDayFilled({{ $i }}).entry.start_time && isDayFilled({{ $i }}).entry.end_time">
                                <span class="text-[9px] xl:text-xs" x-text="formatTime(isDayFilled({{ $i }}).entry.start_time) + ' à ' + formatTime(isDayFilled({{ $i }}).entry.end_time)"></span>
                            </template>
                            <br>
                            <template x-if="isDayFilled({{ $i }}).entry.start_time_afternoon && isDayFilled({{ $i }}).entry.end_time_afternoon">
                                <span class="text-[9px] xl:text-xs" x-text="formatTime(isDayFilled({{ $i }}).entry.start_time_afternoon) + ' à ' + formatTime(isDayFilled({{ $i }}).entry.end_time_afternoon)"></span>
                            </template>
                        </p>
                    </div>
                </template>

                <template x-if="isDayFilled({{ $i }}).filled">
                    <div class="absolute inset-0 flex flex-col items-center justify-center hidden group-hover:flex">
                        <button class="btn btn-xs xl:btn-sm btn-primary my-1">Modifier</button>
                        <button @click.stop="deleteEntry({{ $i }})" class="btn btn-xs xl:btn-sm btn-danger my-1">Supprimer</button>
                    </div>
                </template>
        </div>
        @endfor
    </div>

    <div x-show="dayModalOpen" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mt-2">
                    <p class="text-sm text-gray-500 mb-5">
                        Vous modifiez les informations pour le jour : <br>
                        <span x-text="formattedSelectedDay" class="font-bold text-xl mt-3 inline-block text-primary"></span>
                    </p>

                    {{-- Formulaire pour saisir les informations du jour --}}
                    <form @submit.prevent="submitDayForm">
                        <div class="my-4">
                            <select x-model="dayData.status" class="w-full border rounded px-3 py-2 outline-none">
                                <option value="bureau">Au bureau</option>
                                <option value="tele_travail">Télé-travail</option>
                                <option value="conge">Congé (VA)</option>
                                <option value="recup">Récupération</option>
                                <option value="css">Congé sans solde (CSS)</option>
                                <option value="indisponible">Indisponible</option>
                            </select>
                        </div>

                        {{-- Ajout du toggle pour l'après-midi --}}
                        <div class="my-4">
                            <label class="block text-center text-sm text-gray-500">Travailler l'après-midi</label>
                            <input type="checkbox" x-model="afternoonEnabled" class="toggle-checkbox">
                        </div>

                        <div class="my-4 flex justify-center items-center" x-show="dayData.status === 'bureau' || dayData.status === 'tele_travail'">
                            <div class="flex gap-2">
                                <div>
                                    <label class="block text-center text-sm text-gray-500">Début matin</label>
                                    <input type="time" x-model="dayData.start_time" class="w-full border rounded px-3 py-2 outline-none">
                                </div>
                                <div>
                                    <label class="block text-center text-sm text-gray-500">Fin matin</label>
                                    <input type="time" x-model="dayData.end_time" class="w-full border rounded px-3 py-2 outline-none">
                                </div>
                                <div x-show="afternoonEnabled">
                                    <label class="block text-center text-sm text-gray-500">Début aprem</label>
                                    <input type="time" x-model="dayData.start_time_afternoon" class="w-full border rounded px-3 py-2 outline-none">
                                </div>
                                <div x-show="afternoonEnabled">
                                    <label class="block text-center text-sm text-gray-500">Fin aprem</label>
                                    <input type="time" x-model="dayData.end_time_afternoon" class="w-full border rounded px-3 py-2 outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="items-center px-4 py-3">
                            <button type="button" @click="dayModalOpen = false" class="btn btn-neutral">Annuler</button>
                            <button type="submit" class="btn btn-primary" x-text="isEditing ? 'Modifier' : 'Ajouter'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
    function calendar(userId, userEntries, currentYear, currentMonth, startTime, endTime, startTimeAfternoon, endTimeAfternoon) {
        return {
            selectedMonthYear: `${currentYear}-${currentMonth < 10 ? '0' : ''}${currentMonth}`,
            monthYearOptions: {},
            daysInMonthArray: Array(35).fill(null),
            dayModalOpen: false,
            selectedDay: null,
            selectedDate: null,
            planningEntries: userEntries,
            isEditing: false,
            entryIdToEdit: null,
            afternoonEnabled: true,
            dayData: {
                status: 'bureau',
                start_time: startTime,
                end_time: endTime,
                start_time_afternoon: startTimeAfternoon,
                end_time_afternoon: endTimeAfternoon
            },
            months: {
                '1': 'Janvier',
                '2': 'Février',
                '3': 'Mars',
                '4': 'Avril',
                '5': 'Mai',
                '6': 'Juin',
                '7': 'Juillet',
                '8': 'Août',
                '9': 'Septembre',
                '10': 'Octobre',
                '11': 'Novembre',
                '12': 'Décembre'
            },
            getHolidayName(date) {
                const holidayNames = {
                    '2024-01-01': 'Jour de l’an',
                    '2024-04-01': 'Lundi de Pâques',
                    '2024-05-01': 'Fête du travail',
                    '2024-05-09': 'Ascension',
                    '2024-05-20': 'Pentecôte',
                    '2024-07-21': 'Fête nationale',
                    '2024-08-15': 'L’Assomption',
                    '2024-11-01': 'Toussaint',
                    '2024-11-11': 'Fête de l’armistice',
                    '2024-12-25': 'Noël',
                };
                if (date) {
                    return holidayNames[date] || '';
                } else {
                    return holidayNames;
                }
            },

            init() {
                this.populateMonthYearOptions();
                this.updateCalendar();
            },

            formatTime(timeString) {
                return timeString ? timeString.substr(0, 5) : '';
            },

            formatStatus(status) {
                switch (status) {
                    case 'bureau':
                        return 'BUREAU';
                    case 'tele_travail':
                        return 'TÉLÉ-TRAVAIL';
                    case 'recup':
                        return 'RÉCUPERATION';
                    case 'conge':
                        return 'CONGÉ';
                    case 'css':
                        return 'CONGÉ SS';
                    case 'indisponible':
                        return 'INDISPONIBLE';
                    default:
                        return status;
                }
            },

            populateMonthYearOptions() {
                let options = {};
                for (let year = currentYear; year <= currentYear + 1; year++) {
                    for (let month = 1; month <= 12; month++) {
                        if (year === currentYear && month < currentMonth) continue;
                        const monthYear = `${year}-${month < 10 ? '0' : ''}${month}`;
                        options[monthYear] = this.months[month] + ' ' + year;
                    }
                }
                this.monthYearOptions = options;
            },

            updateCalendar() {
                const [year, month] = this.selectedMonthYear.split('-').map(Number);
                const firstDayOfMonth = new Date(year, month - 1, 1).getDay();
                const daysInMonth = new Date(year, month, 0).getDate();

                this.daysInMonthArray.fill(null);

                let offset = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;
                for (let i = offset, day = 1; day <= daysInMonth; i++, day++) {
                    this.daysInMonthArray[i] = day;
                }
            },

            decrementMonth() {
                const [year, month] = this.selectedMonthYear.split('-').map(Number);
                this.selectedMonthYear = month === 1 ? `${year - 1}-12` : `${year}-${month - 1 < 10 ? '0' : ''}${month - 1}`;
                this.updateCalendar();
            },

            incrementMonth() {
                const [year, month] = this.selectedMonthYear.split('-').map(Number);
                this.selectedMonthYear = month === 12 ? `${year + 1}-01` : `${year}-${month + 1 < 10 ? '0' : ''}${month + 1}`;
                this.updateCalendar();
            },

            isDecrementDisabled() {
                return this.selectedMonthYear === `${currentYear}-${currentMonth < 10 ? '0' : ''}${currentMonth}`;
            },

            isIncrementDisabled() {
                const [year, month] = this.selectedMonthYear.split('-').map(Number);
                return year === currentYear + 1 && month === 12;
            },

            isDayFilled(dayIndex) {
                const selectedDay = this.daysInMonthArray[dayIndex - 1];
                if (selectedDay === null || selectedDay === undefined) {
                    return {
                        filled: false,
                        entryId: null,
                        entry: {}
                    };
                }

                try {
                    const [year, month] = this.selectedMonthYear.split('-').map(Number);
                    const date = `${year}-${String(month).padStart(2, '0')}-${String(selectedDay).padStart(2, '0')}`;
                    const entry = this.planningEntries.find(entry => entry.date === date);

                    if (this.getHolidayName(date)) {
                        return {
                            filled: false, // Assurez-vous que 'filled' est false pour les jours fériés
                            entryId: null,
                            entry: {},
                            holiday: true,
                            holidayName: this.getHolidayName(date)
                        };
                    }

                    return {
                        filled: !!entry,
                        entryId: entry ? entry.id : null,
                        entry: entry || {}
                    };
                } catch (e) {
                    console.error('Erreur lors de la création de la date:', e);
                    return {
                        filled: false,
                        entryId: null,
                        entry: {}
                    };
                }
            },

            openDayModal(day) {
                const {
                    filled,
                    entryId
                } = this.isDayFilled(day);

                if (!this.daysInMonthArray[day - 1]) {
                    console.log('Ce jour n\'existe pas.');
                    return;
                }
                this.isEditing = filled;
                this.entryIdToEdit = filled ? entryId : null;

                if (filled) {
                    // Charger les données de l'entrée existante
                    const entryToEdit = this.planningEntries.find(entry => entry.id === entryId);
                    if (entryToEdit) {
                        this.dayData.status = entryToEdit.status;
                        this.dayData.start_time = entryToEdit.start_time.substr(0, 5);
                        this.dayData.end_time = entryToEdit.end_time.substr(0, 5);
                        this.dayData.start_time_afternoon = entryToEdit.start_time_afternoon ? entryToEdit.start_time_afternoon.substr(0, 5) : null;
                        this.dayData.end_time_afternoon = entryToEdit.end_time_afternoon ? entryToEdit.end_time_afternoon.substr(0, 5) : null;
                    }
                } else {
                    // Réinitialiser `dayData` pour une nouvelle entrée
                    this.resetDayData();
                }

                this.afternoonEnabled = this.dayData.start_time_afternoon && this.dayData.end_time_afternoon;
                this.selectedDay = day;

                // Construction de la chaîne de date au format YYYY-MM-DD
                const selectedYear = this.selectedMonthYear.split('-')[0];
                const selectedMonth = this.selectedMonthYear.split('-')[1];
                const selectedDay = this.daysInMonthArray[day - 1].toString().padStart(2, '0');
                this.selectedDate = `${selectedYear}-${selectedMonth}-${selectedDay}`;

                this.dayModalOpen = true;
                console.log('Ouverture de la modal.');
            },

            submitDayForm() {
                const url = this.isEditing ? `/mon-planning/update/${this.entryIdToEdit}` : '/mon-planning/store';

                let bodyData = {
                    user_id: userId,
                    date: this.selectedDate,
                    status: this.dayData.status,
                    start_time: this.dayData.start_time,
                    end_time: this.dayData.end_time,
                    start_time_afternoon: this.afternoonEnabled ? this.dayData.start_time_afternoon : null,
                    end_time_afternoon: this.afternoonEnabled ? this.dayData.end_time_afternoon : null,
                };

                if (this.isEditing) {
                    bodyData._method = 'PATCH';
                }

                fetch(url, {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(bodyData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            Swal.fire({
                                title: 'Erreur',
                                text: 'Une erreur est survenue, veuillez réessayer.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.fire({
                            title: 'Succès',
                            text: 'Les informations ont été enregistrées avec succès.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000, // Le toast disparaît après 3000 millisecondes (3 secondes)
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                        this.reloadPlanningEntries();
                        this.dayModalOpen = false;
                    })
                    .catch(error => {
                        console.log(error);
                        Swal.fire({
                            title: 'Erreur',
                            text: 'Il y a eu un problème lors de l\'enregistrement des informations.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            },

            reloadPlanningEntries() {
                fetch('/mon-planning/show/')
                    .then(response => {
                        console.log('Response:', response);
                        if (!response.ok) {
                            Swal.fire({
                                title: 'Erreur',
                                text: 'Une erreur est survenue, veuillez réessayer.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        this.planningEntries = data.entries;
                        this.updateCalendar(); // Mettre à jour le calendrier avec les nouvelles données
                    })
                    .catch(error => {
                        console.log('Erreur lors du chargement des entrées : ', error);
                    });
            },

            deleteEntry(dayIndex) {
                const entryId = this.isDayFilled(dayIndex).entryId;

                Swal.fire({
                    title: 'Êtes-vous sûr?',
                    text: "Vous ne pourrez pas revenir en arrière!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui, supprimez-le!',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('/mon-planning/destroy/' + entryId)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Erreur de réseau');
                                }
                                return response.json();
                            })
                            .then(data => {
                                Swal.fire({
                                    title: 'Succès',
                                    text: 'Votre entrée a été supprimée.',
                                    icon: 'success',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000, // Le toast disparaît après 3000 millisecondes (3 secondes)
                                    timerProgressBar: true,
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer)
                                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                                    }
                                });
                                this.reloadPlanningEntries();
                                this.dayModalOpen = false;
                            })
                            .catch(error => {
                                console.error(error);
                                Swal.fire({
                                    title: 'Erreur',
                                    text: 'Il y a eu un problème lors de la suppression de l\'entrée.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            });
                    }
                });
            },

            fillWeekAutomatically(weekNumber) {
                const [year, month] = this.selectedMonthYear.split('-').map(Number);
                const holidays = Object.keys(this.getHolidayName());

                fetch(`/mon-planning/fill-week/${year}/${month}/${weekNumber}`, {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            holidays
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur de réseau');
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.fire({
                            title: 'Succès',
                            text: 'Votre semaine a été remplie automatiquement.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        this.reloadPlanningEntries();
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire({
                            title: 'Erreur',
                            text: 'Un problème est survenu lors du remplissage automatique de la semaine.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            },

            resetDayData() {
                this.dayData.status = 'bureau';
                this.dayData.start_time = startTime;
                this.dayData.end_time = endTime;
                this.dayData.start_time_afternoon = startTimeAfternoon;
                this.dayData.end_time_afternoon = endTimeAfternoon;
            },

            get formattedSelectedDay() {
                if (!this.selectedDay || !this.daysInMonthArray[this.selectedDay - 1]) return '';

                const selectedDate = new Date(this.selectedMonthYear.split('-')[0], this.selectedMonthYear.split('-')[1] - 1, this.daysInMonthArray[this.selectedDay - 1]);
                return selectedDate.toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            },
        };
    }
</script>
@endpush

@endsection