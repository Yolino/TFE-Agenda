@extends("app")
@section("title", "Mon planning - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')

    <div class="card-eg">
        <h1 class="text-4xl font-medium">Mon planning</h1>
    </div>

    <div x-data='calendar({{ $targetUserId }}, @json($userEntries), @json($currentYear), @json($currentMonth), @json($startTime), @json($endTime), @json($startTimeAfternoon), @json($endTimeAfternoon))' class="card-eg">

        {{-- Sélecteur d'utilisateur pour les admins --}}
        @if(auth()->user()->is_admin() && $users->isNotEmpty())
        <div class="flex items-center gap-3 mb-4 p-3 bg-base-200 rounded-box">
            <i class="fa-duotone fa-user-gear text-primary"></i>
            <label class="font-bold text-sm uppercase">Gérer le planning de :</label>
            <select @change="switchUser($event.target.value)" class="select select-bordered" x-ref="userSelector">
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ $user->id == $targetUserId ? 'selected' : '' }}>
                    {{ $user->firstname }} {{ $user->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

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

        {{-- Indicateur copier/coller (usage unique) --}}
        <div x-show="copiedData" x-transition class="flex items-center justify-between mb-4 p-2 bg-info/20 rounded-box text-sm">
            <span><i class="fa-duotone fa-clipboard mr-1"></i> Tuile copiée — cliquez sur un jour vide pour coller (usage unique)</span>
            <button @click="cancelCopy()" class="btn btn-xs btn-ghost">Annuler</button>
        </div>

        <div class="grid grid-cols-7 gap-4">
            @foreach($weekDays as $day)
            <div class="font-bold text-center border-b-2">
                {{ $day }}
            </div>
            @endforeach

            @for ($i = 1; $i <= 35; $i++) <div class="border aspect-square flex relative group" :class="{
                    'hover:bg-secondary hover:text-white cursor-pointer': daysInMonthArray[{{ $i - 1 }}] && !getHoliday({{ $i }}),
                    'cursor-not-allowed opacity-90': getHoliday({{ $i }}),
                    'bg-success': isDayFilled({{ $i }}).filled,
                    'ring-2 ring-info ring-offset-1': copiedDayIndex === {{ $i }}
                }" @click="handleDayClick({{ $i }})">
                @php
                $weekNumber = intdiv($i + 6, 7);
                @endphp
                <span x-text="daysInMonthArray[{{ $i - 1 }}]" class="text-[7px] xl:text-xs font-bold p-3 absolute top-0 left-0"></span>
                @if ($i % 7===1) <button @click.stop="fillWeekAutomatically({{ $weekNumber }})" class="btn btn-xs text-[7px] xl:text-xs btn-ghost absolute top-2 right-2 z-10 opacity-25 hover:opacity-100 tooltip" data-tip="Remplir automatiquement">auto <i class="fa-duotone fa-arrow-right"></i></button>@endif

                <template x-if="isDayFilled({{ $i }}).filled">
                    <div class="absolute inset-0 flex flex-col items-center justify-center" :class="{
                        'bg-blue-200/[0.3]': isDayFilled({{ $i }}).entry.status === 'bureau',
                        'bg-green-500/[0.4]': isDayFilled({{ $i }}).entry.status === 'tele_travail',
                        'bg-yellow-300/[0.9]': isDayFilled({{ $i }}).entry.status === 'conge',
                        'bg-red-400/[0.9]': isDayFilled({{ $i }}).entry.status === 'indisponible',
                        'bg-purple-400/[0.9]': isDayFilled({{ $i }}).entry.status === 'recup',
                        'bg-orange-400/[0.8]': isDayFilled({{ $i }}).entry.status === 'css',
                        'bg-pink-400/[0.9]': isDayFilled({{ $i }}).entry.status === 'maladie',
                        'bg-teal-400/[0.9]': isDayFilled({{ $i }}).entry.status === 'jour_ferie'
                    }">
                        <p x-text="formatStatus(isDayFilled({{ $i }}).entry.status)" class="text-[10px] xl:text-base text-center font-bold"></p>

                        {{-- Affichage du type de congé si lié à une demande acceptée --}}
                        <template x-if="isDayFilled({{ $i }}).entry.demande_conge_type && isDayFilled({{ $i }}).entry.demande_conge_status === 'acceptee'">
                            <p class="text-[8px] xl:text-xs text-center opacity-80 italic" x-text="congeTypeLabels[isDayFilled({{ $i }}).entry.demande_conge_type] || isDayFilled({{ $i }}).entry.demande_conge_type"></p>
                        </template>

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
                        <button class="btn btn-xs xl:btn-sm btn-primary my-1" @click.stop="openDayModal({{ $i }})">Modifier</button>
                        <button @click.stop="copyEntry({{ $i }})" class="btn btn-xs xl:btn-sm btn-secondary my-1"><i class="fa-duotone fa-copy mr-1"></i>Copier</button>
                        <button @click.stop="deleteEntry({{ $i }})" class="btn btn-xs xl:btn-sm btn-danger my-1">Supprimer</button>
                    </div>
                </template>

                <template x-if="getHoliday({{ $i }}) && daysInMonthArray[{{ $i - 1 }}]">
                    <div class="absolute inset-0 flex flex-col items-center justify-center bg-teal-400/[0.95] z-10">
                        <p class="text-[10px] xl:text-base text-center font-bold">JOUR FÉRIÉ</p>
                        <p class="text-[8px] xl:text-xs text-center opacity-80 italic px-1 truncate w-full text-center" x-text="getHoliday({{ $i }})"></p>
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
                                <option value="maladie">Maladie</option>
                                <option value="jour_ferie">Jour férié</option>
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
            holidaysBE: {},
            holidaysInitialized: false,
            selectedMonthYear: `${currentYear}-${currentMonth < 10 ? '0' : ''}${currentMonth}`,
            monthYearOptions: {},
            daysInMonthArray: Array(35).fill(null),
            dayModalOpen: false,
            selectedDay: null,
            selectedDate: null,
            planningEntries: userEntries,
            isEditing: false,
            entryIdToEdit: null,
            originalStatus: null,
            originalDemandeCongeStatus: null,
            afternoonEnabled: true,
            congeTypeLabels: {
                'recup': 'Récup.',
                'conge': 'Congé (VA)',
                'css': 'CSS',
                'visite': 'Visite méd.',
                'autre': 'Autre'
            },
            copiedData: null,
            copiedDayIndex: null,
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
            init() {
                this.populateMonthYearOptions();
                this.loadHolidays();
                this.updateCalendar();
            },

            loadHolidays() {
                try {
                    if (typeof window.Holidays === 'undefined') return;
                    const hd = new window.Holidays('BE');
                    const map = {};
                    for (let y = currentYear; y <= currentYear + 1; y++) {
                        (hd.getHolidays(y) || []).forEach(h => {
                            if (h.type !== 'public') return;
                            const d = h.date.substring(0, 10);
                            map[d] = h.name;
                        });
                    }
                    this.holidaysBE = map;
                    this.holidaysInitialized = true;
                } catch (e) {
                    console.warn('date-holidays init failed', e);
                }
            },

            getHoliday(dayIndex) {
                const selectedDay = this.daysInMonthArray[dayIndex - 1];
                if (!selectedDay) return null;
                const [year, month] = this.selectedMonthYear.split('-').map(Number);
                const date = `${year}-${String(month).padStart(2, '0')}-${String(selectedDay).padStart(2, '0')}`;
                return this.holidaysBE[date] || null;
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
                    case 'maladie':
                        return 'MALADIE';
                    case 'jour_ferie':
                        return 'JOUR FÉRIÉ';
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
                    return { filled: false, entryId: null, entry: {} };
                }
                const [year, month] = this.selectedMonthYear.split('-').map(Number);
                const date = `${year}-${String(month).padStart(2, '0')}-${String(selectedDay).padStart(2, '0')}`;
                const entry = this.planningEntries.find(e => e.date === date);
                return {
                    filled: !!entry,
                    entryId: entry ? entry.id : null,
                    entry: entry || {}
                };
            },

            // Gestion du clic sur un jour : coller si copie active, sinon ouvrir le modal
            handleDayClick(day) {
                // Bloquer toute interaction sur un jour férié
                if (this.getHoliday(day)) {
                    Swal.fire({
                        title: 'Jour férié',
                        text: 'Aucune action n\'est possible sur un jour férié.',
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                    return;
                }

                if (this.copiedData && !this.isDayFilled(day).filled && this.daysInMonthArray[day - 1]) {
                    this.pasteEntry(day);
                } else {
                    this.openDayModal(day);
                }
            },

            // Copier les données d'une tuile
            copyEntry(dayIndex) {
                const { entry } = this.isDayFilled(dayIndex);
                if (!entry) return;

                this.copiedData = {
                    status: entry.status,
                    start_time: entry.start_time ? entry.start_time.substr(0, 5) : null,
                    end_time: entry.end_time ? entry.end_time.substr(0, 5) : null,
                    start_time_afternoon: entry.start_time_afternoon ? entry.start_time_afternoon.substr(0, 5) : null,
                    end_time_afternoon: entry.end_time_afternoon ? entry.end_time_afternoon.substr(0, 5) : null,
                };
                this.copiedDayIndex = dayIndex;

                Swal.fire({
                    title: 'Copié !',
                    text: 'Cliquez sur un jour vide pour coller.',
                    icon: 'info',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                });
            },

            cancelCopy() {
                this.copiedData = null;
                this.copiedDayIndex = null;
            },

            // Coller les données copiées sur un autre jour
            pasteEntry(dayIndex) {
                if (!this.copiedData || !this.daysInMonthArray[dayIndex - 1]) return;

                const selectedYear = this.selectedMonthYear.split('-')[0];
                const selectedMonth = this.selectedMonthYear.split('-')[1];
                const selectedDay = this.daysInMonthArray[dayIndex - 1].toString().padStart(2, '0');
                const targetDate = `${selectedYear}-${selectedMonth}-${selectedDay}`;

                let bodyData = {
                    user_id: userId,
                    date: targetDate,
                    status: this.copiedData.status,
                    start_time: this.copiedData.start_time,
                    end_time: this.copiedData.end_time,
                    start_time_afternoon: this.copiedData.start_time_afternoon,
                    end_time_afternoon: this.copiedData.end_time_afternoon,
                };

                fetch('/mon-planning/store', {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(bodyData)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Erreur');
                    return response.json();
                })
                .then(data => {
                    Swal.fire({
                        title: 'Collé !',
                        text: 'La tuile a été dupliquée avec succès.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                    // Le presse-papier est à usage unique : réinitialiser après coller
                    this.cancelCopy();
                    this.reloadPlanningEntries();
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire({
                        title: 'Erreur',
                        text: 'Impossible de coller la tuile.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            },

            // Changement d'utilisateur (admin)
            switchUser(newUserId) {
                window.location.href = `/mon-planning/?user_id=${newUserId}`;
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
                    const entryToEdit = this.planningEntries.find(entry => entry.id === entryId);
                    if (entryToEdit) {
                        this.dayData.status = entryToEdit.status;
                        this.originalStatus = entryToEdit.status;
                        this.originalDemandeCongeStatus = entryToEdit.demande_conge_status ?? null;
                        this.dayData.start_time = entryToEdit.start_time ? entryToEdit.start_time.substr(0, 5) : startTime;
                        this.dayData.end_time = entryToEdit.end_time ? entryToEdit.end_time.substr(0, 5) : endTime;
                        this.dayData.start_time_afternoon = entryToEdit.start_time_afternoon ? entryToEdit.start_time_afternoon.substr(0, 5) : startTimeAfternoon;
                        this.dayData.end_time_afternoon = entryToEdit.end_time_afternoon ? entryToEdit.end_time_afternoon.substr(0, 5) : endTimeAfternoon;
                    }
                } else {
                    this.originalStatus = null;
                    this.originalDemandeCongeStatus = null;
                    this.resetDayData();
                }

                this.afternoonEnabled = this.dayData.start_time_afternoon && this.dayData.end_time_afternoon;
                this.selectedDay = day;

                const selectedYear = this.selectedMonthYear.split('-')[0];
                const selectedMonth = this.selectedMonthYear.split('-')[1];
                const selectedDayStr = this.daysInMonthArray[day - 1].toString().padStart(2, '0');
                this.selectedDate = `${selectedYear}-${selectedMonth}-${selectedDayStr}`;

                this.dayModalOpen = true;
            },

            submitDayForm() {
                const congeStatuts = ['acceptee', 'envoyee'];
                const congeTypes = ['conge', 'recup', 'css'];

                const isMaladieChange = this.originalStatus === 'maladie' && this.dayData.status !== 'maladie';
                const isCongeValideChange = congeTypes.includes(this.originalStatus)
                    && this.dayData.status !== this.originalStatus
                    && congeStatuts.includes(this.originalDemandeCongeStatus);

                if (isMaladieChange) {
                    Swal.fire({
                        title: 'Attention',
                        html: 'Ce jour est couvert par un <strong>certificat médical</strong>.<br>Êtes-vous sûr de vouloir changer le statut ?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Oui, modifier quand même',
                        cancelButtonText: 'Annuler',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-error',
                            cancelButton: 'btn btn-neutral ml-3',
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.sendDayForm();
                        }
                    });
                    return;
                }

                if (isCongeValideChange) {
                    Swal.fire({
                        title: 'Attention',
                        html: 'Ce jour fait partie d\'un <strong>congé validé</strong>.<br>Êtes-vous sûr de vouloir changer le statut ?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Oui, modifier quand même',
                        cancelButtonText: 'Annuler',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-error',
                            cancelButton: 'btn btn-neutral ml-3',
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.sendDayForm();
                        }
                    });
                    return;
                }

                this.sendDayForm();
            },

            sendDayForm() {
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
                            timer: 3000,
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
                fetch(`/mon-planning/show/?user_id=${userId}`)
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
                        this.planningEntries = data.entries;
                        this.updateCalendar();
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
                                    timer: 3000,
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

                fetch(`/mon-planning/fill-week/${year}/${month}/${weekNumber}`, {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            holidays: Object.keys(this.holidaysBE),
                            user_id: userId
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
