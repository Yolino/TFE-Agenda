{{--
    Modale d'édition d'une entrée de planning — partagée entre :
    - le planning perso (calendar() factory)
    - la grille admin (adminPlanningGrid() factory)

    Les deux factories spread `dayModalMixin()` pour bénéficier de l'état/méthodes ci-dessous.
    L'ouverture se fait via `openDayModalFor({ userId, userName, date, entry })`.
--}}

<div x-show="dayModalOpen" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4" style="display: none;">
    <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-md relative max-h-[90vh] overflow-y-auto p-5">
        <div class="mt-3 text-center">
            <div class="mt-2">
                <p class="text-sm text-gray-500 mb-5">
                    <span x-text="targetUserName ? 'Modification pour ' + targetUserName + ' — ' : 'Vous modifiez les informations pour le jour :'"></span>
                    <br>
                    <span x-text="formattedSelectedDayLabel" class="font-bold text-xl mt-3 inline-block text-primary"></span>
                </p>

                <form @submit.prevent="submitDayForm">
                    <div class="my-4">
                        <select x-model="dayData.status" @change="if (dayData.status !== 'custom') dayData.custom = ''"
                                class="w-full border rounded px-3 py-2 outline-none">
                            <option value="bureau">Au bureau</option>
                            <option value="tele_travail">Télé-travail</option>
                            <option value="recup">Récupération</option>
                            <option value="custom">Personnalisé…</option>
                            <template x-if="isAdmin">
                                <optgroup label="Réservé aux admins">
                                    <option value="conge">Congé (VA)</option>
                                    <option value="css">Congé sans solde (CSS)</option>
                                    <option value="indisponible">Indisponible</option>
                                    <option value="maladie">Maladie</option>
                                    <option value="jour_ferie">Jour férié</option>
                                </optgroup>
                            </template>
                        </select>
                        {{-- Champ texte visible uniquement quand Personnalisé est choisi --}}
                        <template x-if="dayData.status === 'custom'">
                            <input type="text" x-model="dayData.custom"
                                   class="w-full border rounded px-3 py-2 text-sm outline-none mt-2"
                                   placeholder="Ex. Formation, Déplacement, Mission…"
                                   maxlength="255">
                        </template>
                    </div>

                    <div class="my-4">
                        <label class="block text-center text-sm text-gray-500">Travailler l'après-midi</label>
                        <input type="checkbox" x-model="afternoonEnabled" class="toggle-checkbox">
                    </div>

                    <div class="my-4" x-show="dayData.status === 'bureau' || dayData.status === 'tele_travail'">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-center text-xs text-gray-500 mb-1">Début matin</label>
                                <input type="time" x-model="dayData.start_time" class="w-full border rounded px-2 py-1.5 text-sm outline-none">
                            </div>
                            <div>
                                <label class="block text-center text-xs text-gray-500 mb-1">Fin matin</label>
                                <input type="time" x-model="dayData.end_time" class="w-full border rounded px-2 py-1.5 text-sm outline-none">
                            </div>
                            <div x-show="afternoonEnabled">
                                <label class="block text-center text-xs text-gray-500 mb-1">Début après-midi</label>
                                <input type="time" x-model="dayData.start_time_afternoon" class="w-full border rounded px-2 py-1.5 text-sm outline-none">
                            </div>
                            <div x-show="afternoonEnabled">
                                <label class="block text-center text-xs text-gray-500 mb-1">Fin après-midi</label>
                                <input type="time" x-model="dayData.end_time_afternoon" class="w-full border rounded px-2 py-1.5 text-sm outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="items-center px-4 py-3 flex justify-center gap-2 flex-wrap">
                        <button type="button" @click="dayModalOpen = false" class="btn btn-neutral">Annuler</button>
                        <template x-if="isEditing">
                            <button type="button" @click="deleteCurrentEntry()" class="btn btn-error">
                                <i class="fa-solid fa-trash mr-1"></i> Supprimer
                            </button>
                        </template>
                        <button type="submit" class="btn btn-primary" x-text="isEditing ? 'Modifier' : 'Ajouter'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    /**
     * Mixin Alpine partagé pour la modale d'édition de planning.
     * Spread dans calendar() (perso) et adminPlanningGrid() (admin).
     *
     * Chaque factory consommatrice doit :
     *   - définir `afterSubmit()` (rechargement après succès)
     *   - définir `afterDelete()` (rechargement après suppression)
     *   - passer des défauts via `dayModalMixin({ defaults, isAdmin })`
     */
    function dayModalMixin(opts = {}) {
        const defaults = opts.defaults || {
            start: '08:00', end: '12:00',
            start_afternoon: '13:00', end_afternoon: '17:00'
        };

        return {
            // --- État de la modale ---
            dayModalOpen: false,
            isEditing: false,
            entryIdToEdit: null,
            selectedDate: null,
            formattedSelectedDayLabel: '',
            targetUserId: null,
            targetUserName: null,
            originalStatus: null,
            originalDemandeCongeStatus: null,
            afternoonEnabled: true,
            isAdmin: !!opts.isAdmin,
            adminOnlyStatuses: ['conge', 'css', 'indisponible', 'maladie', 'jour_ferie'],
            dayData: {
                status: 'bureau',
                start_time: defaults.start,
                end_time: defaults.end,
                start_time_afternoon: defaults.start_afternoon,
                end_time_afternoon: defaults.end_afternoon,
                custom: '',
            },
            _modalDefaults: defaults,

            // --- Helpers ---
            formatTime(t) { return t ? t.substr(0, 5) : ''; },

            resetDayData() {
                this.dayData.status = 'bureau';
                this.dayData.start_time = this._modalDefaults.start;
                this.dayData.end_time = this._modalDefaults.end;
                this.dayData.start_time_afternoon = this._modalDefaults.start_afternoon;
                this.dayData.end_time_afternoon = this._modalDefaults.end_afternoon;
                this.dayData.custom = '';
            },

            /**
             * Ouvre la modale dans un contexte donné.
             * @param {Object} ctx - { userId, userName, date (YYYY-MM-DD), entry, label }
             */
            openDayModalFor(ctx) {
                this.targetUserId = ctx.userId;
                this.targetUserName = ctx.userName || null;
                this.selectedDate = ctx.date;
                this.formattedSelectedDayLabel = ctx.label || this._formatDateFR(ctx.date);

                const entry = ctx.entry || null;
                this.isEditing = !!entry;
                this.entryIdToEdit = entry ? entry.id : null;

                if (entry) {
                    this.dayData.status = entry.status;
                    this.originalStatus = entry.status;
                    this.originalDemandeCongeStatus = entry.demande_conge_status ?? null;
                    this.dayData.start_time = entry.start_time ? entry.start_time.substr(0, 5) : this._modalDefaults.start;
                    this.dayData.end_time = entry.end_time ? entry.end_time.substr(0, 5) : this._modalDefaults.end;
                    this.dayData.start_time_afternoon = entry.start_time_afternoon ? entry.start_time_afternoon.substr(0, 5) : this._modalDefaults.start_afternoon;
                    this.dayData.end_time_afternoon = entry.end_time_afternoon ? entry.end_time_afternoon.substr(0, 5) : this._modalDefaults.end_afternoon;
                    this.dayData.custom = entry.custom || '';
                } else {
                    this.originalStatus = null;
                    this.originalDemandeCongeStatus = null;
                    this.resetDayData();
                }

                this.afternoonEnabled = !!(this.dayData.start_time_afternoon && this.dayData.end_time_afternoon);
                this.dayModalOpen = true;
            },

            _formatDateFR(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr + 'T00:00:00');
                return d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            },

            // --- Soumission ---
            submitDayForm() {
                if (!this.isAdmin && this.adminOnlyStatuses.includes(this.dayData.status)) {
                    Swal.fire({ title: 'Action interdite', text: 'Ce statut est réservé aux administrateurs.', icon: 'error', confirmButtonText: 'OK' });
                    return;
                }
                if (this.dayData.status === 'custom' && !this.dayData.custom) {
                    Swal.fire({ title: 'Sélection incomplète', text: 'Veuillez saisir un libellé pour le type personnalisé.', icon: 'warning', confirmButtonText: 'OK' });
                    return;
                }

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
                        icon: 'warning', showCancelButton: true,
                        confirmButtonText: 'Oui, modifier quand même', cancelButtonText: 'Annuler',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-error', cancelButton: 'btn btn-neutral ml-3' },
                    }).then((r) => { if (r.isConfirmed) this.sendDayForm(); });
                    return;
                }

                if (isCongeValideChange) {
                    Swal.fire({
                        title: 'Attention',
                        html: 'Ce jour fait partie d\'un <strong>congé validé</strong>.<br>Êtes-vous sûr de vouloir changer le statut ?',
                        icon: 'warning', showCancelButton: true,
                        confirmButtonText: 'Oui, modifier quand même', cancelButtonText: 'Annuler',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-error', cancelButton: 'btn btn-neutral ml-3' },
                    }).then((r) => { if (r.isConfirmed) this.sendDayForm(); });
                    return;
                }

                this.sendDayForm();
            },

            sendDayForm() {
                const url = this.isEditing ? `/mon-planning/update/${this.entryIdToEdit}` : '/mon-planning/store';
                const isCustom = this.dayData.status === 'custom';
                const bodyData = {
                    user_id: this.targetUserId,
                    date: this.selectedDate,
                    status: this.dayData.status,
                    start_time: isCustom ? null : this.dayData.start_time,
                    end_time: isCustom ? null : this.dayData.end_time,
                    start_time_afternoon: (isCustom || !this.afternoonEnabled) ? null : this.dayData.start_time_afternoon,
                    end_time_afternoon: (isCustom || !this.afternoonEnabled) ? null : this.dayData.end_time_afternoon,
                    custom: isCustom ? (this.dayData.custom || null) : null,
                };
                if (this.isEditing) bodyData._method = 'PATCH';

                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(bodyData),
                })
                .then(r => { if (!r.ok) throw new Error('Erreur'); return r.json(); })
                .then(data => {
                    Swal.fire({
                        title: 'Succès',
                        text: data?.conge_cancelled
                            ? 'Modification enregistrée et demande de congé annulée.'
                            : 'Les informations ont été enregistrées.',
                        icon: 'success', toast: true, position: 'top-end', showConfirmButton: false,
                        timer: 3000, timerProgressBar: true,
                    });
                    this.dayModalOpen = false;
                    if (typeof this.afterSubmit === 'function') this.afterSubmit();
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({ title: 'Erreur', text: 'Échec de l\'enregistrement.', icon: 'error', confirmButtonText: 'OK' });
                });
            },

            deleteCurrentEntry() {
                if (!this.entryIdToEdit) return;
                const isCongeLinked = ['acceptee', 'envoyee'].includes(this.originalDemandeCongeStatus);
                const warningHtml = isCongeLinked
                    ? "Cette journée est liée à une <strong>demande de congé validée</strong>.<br>La supprimer annulera la demande."
                    : "Vous ne pourrez pas revenir en arrière !";

                Swal.fire({
                    title: 'Êtes-vous sûr ?', html: warningHtml, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                    confirmButtonText: isCongeLinked ? 'Oui, annuler le congé' : 'Oui, supprimer',
                    cancelButtonText: 'Annuler',
                }).then((r) => {
                    if (!r.isConfirmed) return;
                    fetch('/mon-planning/destroy/' + this.entryIdToEdit)
                        .then(resp => { if (!resp.ok) throw new Error('Erreur'); return resp.json(); })
                        .then(data => {
                            Swal.fire({
                                title: 'Succès',
                                text: data?.conge_cancelled ? 'Entrée supprimée et congé annulé.' : 'Entrée supprimée.',
                                icon: 'success', toast: true, position: 'top-end', showConfirmButton: false,
                                timer: 2500, timerProgressBar: true,
                            });
                            this.dayModalOpen = false;
                            if (typeof this.afterDelete === 'function') this.afterDelete();
                            else if (typeof this.afterSubmit === 'function') this.afterSubmit();
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire({ title: 'Erreur', text: 'Échec de la suppression.', icon: 'error', confirmButtonText: 'OK' });
                        });
                });
            },
        };
    }
</script>
@endpush
@endonce
