@extends("app")
@section("title", "Mes congés - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg flex items-center justify-between gap-4 flex-wrap">
        <h1 class="text-4xl font-medium">Mes congés</h1>
        <div class="min-w-[18rem]">
            <x-leave-balance :balance="$balance" compact />
        </div>
    </div>
    @php
    $types = [
        'recup' => 'Récupération',
        'conge' => 'Congé',
        'css' => 'Congé sans solde',
        'visite' => 'Visite médicale',
        'autre' => 'Autre'
    ];
    $statuts = [
        'en_cours' => ['label' => 'Brouillon', 'class' => 'badge-ghost'],
        'envoyee'  => ['label' => 'Envoyée',   'class' => 'badge-info'],
        'acceptee' => ['label' => 'Acceptée',  'class' => 'badge-success'],
        'refusee'  => ['label' => 'Refusée',   'class' => 'badge-error'],
        'annulee'  => ['label' => 'Annulée',   'class' => 'badge-warning'],
    ];

    $formatJours = function ($n) {
        $n = (float) $n;
        $clean = rtrim(rtrim(number_format($n, 1, ',', ' '), '0'), ',');
        return $clean . ' ' . ($n > 1 ? 'jours' : 'jour');
    };
    @endphp
    @php
        $ongletInitial = request()->has('year') ? 'historique' : 'demandes';
        $refuseeIds = $conges->where('status', 'refusee')->pluck('id')->values()->toArray();
    @endphp
    <div class="card-eg flex flex-row gap-4" x-data="{
        onglet: '{{ $ongletInitial }}',
        selectedConge: null,
        toggleDetails(id) { this.selectedConge = this.selectedConge === id ? null : id; },
        archivedIds: JSON.parse(localStorage.getItem('archived_conges') || '[]'),
        refuseeIds: @json($refuseeIds),
        totalConges: {{ $conges->count() }},
        totalHistorique: {{ $historique->count() }},
        get archivedCount() {
            return this.refuseeIds.filter(id => this.archivedIds.includes(id)).length;
        },
        get demandesCount() {
            return this.totalConges - this.archivedCount;
        },
        get historiqueCount() {
            return this.totalHistorique + this.archivedCount;
        },
        isArchived(id) { return this.archivedIds.includes(id); },
        archiveConge(id) {
            this.archivedIds.push(id);
            localStorage.setItem('archived_conges', JSON.stringify(this.archivedIds));
            Swal.fire({
                title: 'Archivée',
                text: 'La demande a été déplacée dans l\'historique.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
            });
        }
    }">
        <div class="basis-3/4">
            <!-- Onglets -->
            <div role="tablist" class="tabs tabs-bordered mb-4">
                <a role="tab" class="tab" :class="onglet === 'demandes' ? 'tab-active' : ''" @click="onglet = 'demandes'">
                    Mes demandes
                    <span class="badge badge-sm badge-primary ml-2" x-show="demandesCount > 0" x-text="demandesCount"></span>
                </a>
                <a role="tab" class="tab" :class="onglet === 'historique' ? 'tab-active' : ''" @click="onglet = 'historique'">
                    Historique
                    <span class="badge badge-sm badge-primary ml-2" x-show="historiqueCount > 0" x-text="historiqueCount"></span>
                </a>
            </div>

            <!-- Onglet : Mes demandes -->
            <div x-show="onglet === 'demandes'">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Nb jours</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($conges as $conge)
                        <tr class="hover cursor-pointer" x-show="!isArchived({{ $conge->id }})" @click="toggleDetails({{ $conge->id }})">
                            <th>
                                <span class="tooltip tooltip-right cursor-pointer" data-tip="Visualiser le PDF" @click.stop="window.open('/mes-conges/pdf/{{$conge->id}}', '_blank')">
                                    <i class="fa-duotone fa-eye"></i>
                                </span>
                            </th>
                            <td class="font-bold">{{ $types[$conge->type] }}</td>
                            <td>du {{ $conge->formattedStartDate }} au {{ $conge->formattedEndDate }}</td>
                            <td>
                                {{ $formatJours($conge->nb_jours) }}
                                @if(fmod((float) $conge->nb_jours, 1) !== 0.0)
                                    <span class="badge badge-xs badge-outline ml-1">½ journée</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statuts[$conge->status]['class'] }}">{{ $statuts[$conge->status]['label'] }}</span>
                                <div class="mt-1"><x-conge-decision :conge="$conge" /></div>
                            </td>
                            <td @click.stop>
                                @if($conge->status === 'en_cours')
                                    <button class="btn btn-sm btn-secondary tooltip update-conge" data-tip="Modifier"
                                        data-conge-id="{{ $conge->id }}"
                                        data-conge-type="{{ $conge->type }}"
                                        data-conge-start="{{ $conge->start_date }}"
                                        data-conge-end="{{ $conge->end_date }}"
                                        data-conge-half="{{ fmod((float) $conge->nb_jours, 1) !== 0.0 ? '1' : '0' }}">
                                        <i class="fa-duotone fa-pen"></i>
                                    </button>
                                    <a href="{{ route('mes-conges.send', ['id' => $conge->id]) }}" class="send-conge-link" data-send-url="{{ route('mes-conges.send', ['id' => $conge->id]) }}">
                                        <button type="button" class="btn btn-sm btn-success ml-1 tooltip" data-tip="Envoyer"><i class="fa-duotone fa-envelope"></i></button>
                                    </a>
                                    <a href="{{ route('mes-conges.destroy', ['id' => $conge->id]) }}" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette demande ?')">
                                        <button class="btn btn-sm btn-error ml-1 tooltip" data-tip="Supprimer">
                                            <i class="fa-duotone fa-trash-can"></i>
                                        </button>
                                    </a>
                                @endif
                                @if(in_array($conge->status, ['envoyee', 'acceptee']) && \Carbon\Carbon::parse($conge->start_date)->startOfDay()->gt(\Carbon\Carbon::today()))
                                    <button type="button" class="btn btn-sm btn-warning tooltip cancel-conge-btn" data-tip="Annuler la demande"
                                        data-cancel-url="{{ route('mes-conges.cancel', ['id' => $conge->id]) }}"
                                        data-conge-status="{{ $conge->status }}">
                                        <i class="fa-duotone fa-ban"></i>
                                    </button>
                                @endif
                                @if($conge->status === 'refusee')
                                    <button @click="archiveConge({{ $conge->id }})" class="btn btn-sm btn-ghost tooltip" data-tip="Déplacer dans l'historique">
                                        <i class="fa-duotone fa-box-archive"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        <tr x-show="selectedConge === {{ $conge->id }} && !isArchived({{ $conge->id }})" x-cloak>
                            <td colspan="6">
                                <x-conge-details :conge="$conge" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500">Aucune demande de congé en cours.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Onglet : Historique -->
            <div x-show="onglet === 'historique'">
                <!-- Filtre par année -->
                @if($anneesDisponibles->isNotEmpty())
                <div class="flex items-center gap-2 mb-3">
                    <label class="text-sm font-bold uppercase">Année :</label>
                    <div class="flex gap-1 flex-wrap">
                        <a href="{{ route('mes-conges.index', ['year' => 'all']) }}"
                           class="btn btn-xs {{ $selectedYear === 'all' ? 'btn-primary' : 'btn-ghost' }}">
                            Toutes
                        </a>
                        @foreach($anneesDisponibles as $annee)
                        <a href="{{ route('mes-conges.index', ['year' => $annee]) }}"
                           class="btn btn-xs {{ $selectedYear == $annee ? 'btn-primary' : 'btn-ghost' }}">
                            {{ $annee }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Nb jours</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Congés refusés archivés depuis l'onglet actif --}}
                        @foreach ($conges as $conge)
                            @if($conge->status === 'refusee')
                            <tr class="hover opacity-80 cursor-pointer" x-show="isArchived({{ $conge->id }})" @click="toggleDetails({{ $conge->id }})">
                                <th>
                                    <span class="tooltip tooltip-right cursor-pointer" data-tip="Visualiser le PDF" @click.stop="window.open('/mes-conges/pdf/{{$conge->id}}', '_blank')">
                                        <i class="fa-duotone fa-eye"></i>
                                    </span>
                                </th>
                                <td class="font-bold">{{ $types[$conge->type] }}</td>
                                <td>du {{ $conge->formattedStartDate }} au {{ $conge->formattedEndDate }}</td>
                                <td>
                                {{ $formatJours($conge->nb_jours) }}
                                @if(fmod((float) $conge->nb_jours, 1) !== 0.0)
                                    <span class="badge badge-xs badge-outline ml-1">½ journée</span>
                                @endif
                            </td>
                                <td>
                                    <span class="badge {{ $statuts[$conge->status]['class'] }}">{{ $statuts[$conge->status]['label'] }}</span>
                                </td>
                            </tr>
                            <tr x-show="selectedConge === {{ $conge->id }} && isArchived({{ $conge->id }})" x-cloak>
                                <td colspan="5">
                                    <x-conge-details :conge="$conge" />
                                </td>
                            </tr>
                            @endif
                        @endforeach
                        {{-- Historique existant --}}
                        @forelse ($historique as $conge)
                        <tr class="hover opacity-80 cursor-pointer" @click="toggleDetails({{ $conge->id }})">
                            <th>
                                <span class="tooltip tooltip-right cursor-pointer" data-tip="Visualiser le PDF" @click.stop="window.open('/mes-conges/pdf/{{$conge->id}}', '_blank')">
                                    <i class="fa-duotone fa-eye"></i>
                                </span>
                            </th>
                            <td class="font-bold">{{ $types[$conge->type] }}</td>
                            <td>du {{ $conge->formattedStartDate }} au {{ $conge->formattedEndDate }}</td>
                            <td>
                                {{ $formatJours($conge->nb_jours) }}
                                @if(fmod((float) $conge->nb_jours, 1) !== 0.0)
                                    <span class="badge badge-xs badge-outline ml-1">½ journée</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statuts[$conge->status]['class'] }}">{{ $statuts[$conge->status]['label'] }}</span>
                                <div class="mt-1"><x-conge-decision :conge="$conge" /></div>
                            </td>
                        </tr>
                        <tr x-show="selectedConge === {{ $conge->id }}" x-cloak>
                            <td colspan="5">
                                <x-conge-details :conge="$conge" />
                            </td>
                        </tr>
                        @empty
                            @if($conges->where('status', 'refusee')->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-gray-500">Aucun congé passé.</td>
                            </tr>
                            @endif
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="basis-1/4">
            <form action="" method="post" x-data="{ sameDay: false, halfDay: false }">
                @method('POST')
                @csrf
                <div class="flex flex-col gap-5">
                    <!-- Type de congé -->
                    <div class="flex flex-col">
                        <label for="type" class="uppercase text-sm font-bold mb-2">Type de congé</label>
                        <select id="type" class="input w-full" name="type" required>
                            <option value="recup">Récupération</option>
                            <option value="conge">Congé</option>
                            <option value="css">Congé sans solde</option>
                            <option value="visite">Visite médicale</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <!-- Date de début -->
                    <div class="flex flex-col">
                        <label for="start_date" class="uppercase text-sm font-bold mb-2">Date de début</label>
                        <input type="date" id="start_date" class="input w-full" name="start_date" required
                               @change="sameDay = $event.target.value && $event.target.value === document.getElementById('end_date').value; if (!sameDay) halfDay = false;" />
                    </div>
                    <!-- Date de fin -->
                    <div class="flex flex-col">
                        <label for="end_date" class="uppercase text-sm font-bold mb-2">Date de fin</label>
                        <input type="date" id="end_date" class="input w-full" name="end_date" required
                               @change="sameDay = $event.target.value && $event.target.value === document.getElementById('start_date').value; if (!sameDay) halfDay = false;" />
                    </div>
                    <!-- Demi-journée -->
                    <div class="flex items-center justify-between gap-2" x-show="sameDay" x-transition>
                        <label for="is_half_day" class="uppercase text-sm font-bold">Demi-journée</label>
                        <input type="hidden" name="is_half_day" :value="halfDay ? 1 : 0">
                        <input type="checkbox" id="is_half_day" class="toggle toggle-primary" x-model="halfDay">
                    </div>
                    <!-- Bouton de soumission -->
                    <div class="flex flex-col">
                        <button class="btn btn-primary w-full">Générer la demande</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de modification -->
<dialog id="editModal" class="modal">
    <div class="modal-box">
        <h3 class="text-lg font-bold">Modifier la demande de congé</h3>
        <form id="editForm" method="POST" class="mt-4">
            @method('PUT')
            @csrf
            <div class="flex flex-col gap-4">
                <div class="flex flex-col">
                    <label for="edit_type" class="uppercase text-sm font-bold mb-2">Type de congé</label>
                    <select id="edit_type" class="input w-full" name="type" required>
                        <option value="recup">Récupération</option>
                        <option value="conge">Congé</option>
                        <option value="css">Congé sans solde</option>
                        <option value="visite">Visite médicale</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label for="edit_start_date" class="uppercase text-sm font-bold mb-2">Date de début</label>
                    <input type="date" id="edit_start_date" class="input w-full" name="start_date" required />
                </div>
                <div class="flex flex-col">
                    <label for="edit_end_date" class="uppercase text-sm font-bold mb-2">Date de fin</label>
                    <input type="date" id="edit_end_date" class="input w-full" name="end_date" required />
                </div>
                <div id="edit_half_day_wrapper" class="flex items-center justify-between gap-2 hidden">
                    <label for="edit_is_half_day" class="uppercase text-sm font-bold">Demi-journée</label>
                    <input type="hidden" id="edit_is_half_day_value" name="is_half_day" value="0">
                    <input type="checkbox" id="edit_is_half_day" class="toggle toggle-primary">
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-neutral" onclick="document.getElementById('editModal').close()">Annuler</button>
                <button type="submit" class="btn btn-primary">Modifier</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push("scripts")
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function validateDateOrder(startEl, endEl) {
            if (!startEl.value || !endEl.value) return true;
            if (new Date(startEl.value) > new Date(endEl.value)) {
                Swal.fire({
                    title: 'Erreur',
                    text: 'La date de début ne peut pas être postérieure à la date de fin.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                endEl.value = '';
                return false;
            }
            return true;
        }

        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        startDateInput.addEventListener('change', () => validateDateOrder(startDateInput, endDateInput));
        endDateInput.addEventListener('change', () => validateDateOrder(startDateInput, endDateInput));

        // Gestion du formulaire de modification
        const editStartDate = document.getElementById('edit_start_date');
        const editEndDate = document.getElementById('edit_end_date');
        const editHalfDayWrapper = document.getElementById('edit_half_day_wrapper');
        const editHalfDayToggle = document.getElementById('edit_is_half_day');
        const editHalfDayValue = document.getElementById('edit_is_half_day_value');

        function syncEditHalfDayVisibility() {
            const isSameDay = editStartDate.value && editStartDate.value === editEndDate.value;
            editHalfDayWrapper.classList.toggle('hidden', !isSameDay);
            if (!isSameDay) {
                editHalfDayToggle.checked = false;
                editHalfDayValue.value = '0';
            }
        }

        editHalfDayToggle.addEventListener('change', () => {
            editHalfDayValue.value = editHalfDayToggle.checked ? '1' : '0';
        });

        editStartDate.addEventListener('change', () => {
            if (validateDateOrder(editStartDate, editEndDate)) syncEditHalfDayVisibility();
        });
        editEndDate.addEventListener('change', () => {
            if (validateDateOrder(editStartDate, editEndDate)) syncEditHalfDayVisibility();
        });

        // Ouverture du modal de modification
        document.querySelectorAll('.update-conge').forEach(button => {
            button.addEventListener('click', function() {
                const congeId = this.getAttribute('data-conge-id');
                const congeType = this.getAttribute('data-conge-type');
                const congeStart = this.getAttribute('data-conge-start');
                const congeEnd = this.getAttribute('data-conge-end');
                const congeHalf = this.getAttribute('data-conge-half') === '1';

                document.getElementById('editForm').action = '/mes-conges/update/' + congeId;
                document.getElementById('edit_type').value = congeType;
                editStartDate.value = congeStart;
                editEndDate.value = congeEnd;
                editHalfDayToggle.checked = congeHalf;
                editHalfDayValue.value = congeHalf ? '1' : '0';
                syncEditHalfDayVisibility();

                document.getElementById('editModal').showModal();
            });
        });

        document.querySelectorAll('.cancel-conge-btn').forEach(button => {
            button.addEventListener('click', function() {
                const cancelUrl = this.getAttribute('data-cancel-url');
                const status = this.getAttribute('data-conge-status');

                const message = status === 'acceptee'
                    ? 'Cette demande a été <strong>acceptée</strong>. L\'annulation supprimera également les jours de congé déjà placés sur votre planning.'
                    : 'L\'annulation retirera votre demande du circuit de validation.';

                Swal.fire({
                    title: 'Annuler la demande ?',
                    html: message + '<br><br>Cette action est définitive.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, annuler',
                    cancelButtonText: 'Retour',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-warning',
                        cancelButton: 'btn btn-neutral ml-3',
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = cancelUrl;
                    }
                });
            });
        });

        document.querySelectorAll('.send-conge-link').forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();

                const sendUrl = this.getAttribute('data-send-url');

                Swal.fire({
                    title: 'Envoyer la demande ?',
                    text: 'Une fois envoyée, vous ne pourrez plus la modifier.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, envoyer',
                    cancelButtonText: 'Annuler',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-success',
                        cancelButton: 'btn btn-neutral ml-3',
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = sendUrl;
                    }
                });
            });
        });
    });
</script>
@endpush
