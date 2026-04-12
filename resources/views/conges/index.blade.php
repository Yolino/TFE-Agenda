@extends("app")
@section("title", "Mes congés - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-4xl font-medium">Mes congés</h1>
    </div>
    <div class="card-eg flex flex-row gap-4">
        <div class="basis-3/4">
            <table class="table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Nb jours</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $types = [
                    'recup' => 'Récupération',
                    'conge' => 'Congé',
                    'css' => 'Congé sans solde',
                    'visite' => 'Visite médicale',
                    'autre' => 'Autre'
                    ];
                    @endphp
                    @foreach ($conges as $conge)
                    <tr class="hover">
                        <th>
                            <span class="tooltip tooltip-right cursor-pointer" data-tip="Visualiser la demande" onclick="window.open('/mes-conges/pdf/{{$conge->id}}', '_blank');">
                                <i class="fa-duotone fa-eye"></i>
                            </span>
                        </th>
                        <td class="font-bold">{{ $types[$conge->type] }}</td>
                        <td>du {{ $conge->formattedStartDate }} au {{ $conge->formattedEndDate }}</td>
                        <td>{{ $conge->nb_jours . ($conge->nb_jours > 1 ? " jours" : " jour") }}</td>
                        <td>
                            <button class="btn btn-sm btn-secondary tooltip update-conge" data-tip="Modifier"><i class="fa-duotone fa-pen"></i></button>
                            <button class="btn btn-sm btn-success ml-1 tooltip" data-tip="Envoyer"><i class="fa-duotone fa-envelope"></i></button>
                            <a href='{{ route('mes-conges.destroy', ['id' => $conge->id]) }}'>
                                <button class="btn btn-sm btn-error ml-1 tooltip" data-tip="Supprimer">
                                    <i class="fa-duotone fa-trash-can"></i>
                                </button>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="basis-1/4">
            <form action="" method="post">
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
                    <!-- Nombre de jours -->
                    <div class="flex flex-col hidden">
                        <label for="nb_jours" class="uppercase text-sm font-bold mb-2">Nombre de jours</label>
                        <input type="number" placeholder="" id="nb_jours" class="input w-full" name="nb_jours" required />
                    </div>
                    <!-- Date de début -->
                    <div class="flex flex-col">
                        <label for="start_date" class="uppercase text-sm font-bold mb-2">Date de début</label>
                        <input type="date" placeholder="" id="start_date" class="input w-full" name="start_date" required />
                    </div>
                    <!-- Date de fin -->
                    <div class="flex flex-col">
                        <label for="end_date" class="uppercase text-sm font-bold mb-2">Date de fin</label>
                        <input type="date" placeholder="" id="end_date" class="input w-full" name="end_date" required />
                    </div>
                    <!-- Bouton de soumission -->
                    <div class="flex flex-col">
                        <button class="btn btn-primary w-full">Générer la demande</button>
                    </div>
                </div>
            </form>
        </div>
        <div x-show="updateModalOpen" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" style="display: none;">
            <div class="relative top-20 mx-auto p-5 border w-1/3 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Modifier la Demande de Congé</h3>
                    <div class="mt-2">
                        <form @submit.prevent="submitEditForm">
                            <div class="items-center px-4 py-3">
                                <button type="button" @click="updateModalOpen = false" class="btn btn-neutral">Annuler</button>
                                <button type="submit" class="btn btn-primary">Modifier</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push("scripts")
<script>
    var updateModalOpen = false;

    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const nbJoursInput = document.getElementById('nb_jours');

        function updateNbJours() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (startDateInput.value && endDateInput.value) {
                if (startDate > endDate) {
                    Swal.fire({
                        title: 'Erreur',
                        text: 'La date de début ne peut pas être postérieure à la date de fin.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    nbJoursInput.value = ''; // Réinitialiser le champ nb_jours
                    endDateInput.value = ''; // Réinitialiser le champ end_date
                    return; // Ne pas continuer avec le calcul
                }

                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Ajouter 1 pour inclure les deux dates
                nbJoursInput.value = diffDays;
            }
        }

        startDateInput.addEventListener('change', updateNbJours);
        endDateInput.addEventListener('change', updateNbJours);

        function openUpdateModal(congeId) {
            fetch(`/mes-conges/update/${congeId}`)
                .then(response => response.json())
                .then(data => {
                    updateModalOpen = true;
                })
                .catch(error => console.error('Erreur:', error));
        }

        document.querySelectorAll('.update-conge').forEach(button => {
            button.addEventListener('click', function() {
                openUpdateModal(this.getAttribute('data-conge-id'));
            });
        });
    });
</script>

@endpush