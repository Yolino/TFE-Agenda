@extends("app")
@section("title", "Justificatifs d'absence - Agenda")

@section("content")
@include("partials.nav")
<div class="p-4">
    @include('partials.flash')
    <div class="card-eg">
        <h1 class="text-4xl font-medium">Justificatifs d'absence</h1>
    </div>

    @php $ongletInitial = request()->has('year') ? 'historique' : 'justificatifs'; @endphp
    <div class="card-eg flex flex-row gap-4" x-data="{ onglet: '{{ $ongletInitial }}' }">
        <div class="basis-3/4">
            <!-- Onglets -->
            <div role="tablist" class="tabs tabs-bordered mb-4">
                <a role="tab" class="tab" :class="onglet === 'justificatifs' ? 'tab-active' : ''" @click="onglet = 'justificatifs'">
                    Mes justificatifs
                    @if($justificatifs->count() > 0)
                        <span class="badge badge-sm badge-primary ml-2">{{ $justificatifs->count() }}</span>
                    @endif
                </a>
                <a role="tab" class="tab" :class="onglet === 'historique' ? 'tab-active' : ''" @click="onglet = 'historique'">
                    Historique
                    @if($historique->count() > 0)
                        <span class="badge badge-sm badge-primary ml-2">{{ $historique->count() }}</span>
                    @endif
                </a>
            </div>

            <!-- Onglet : Mes justificatifs -->
            <div x-show="onglet === 'justificatifs'">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Nb jours</th>
                            <th>Certificat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($justificatifs as $justificatif)
                        <tr class="hover">
                            <td>du {{ $justificatif->formattedStartDate }} au {{ $justificatif->formattedEndDate }}</td>
                            <td>{{ $justificatif->nb_jours . ($justificatif->nb_jours > 1 ? ' jours' : ' jour') }}</td>
                            <td>
                                <a href="{{ asset('storage/' . $justificatif->certificat_medical) }}" target="_blank"
                                   class="btn btn-sm btn-secondary tooltip" data-tip="Voir le certificat">
                                    <i class="fa-duotone fa-file-medical"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-500">Aucun justificatif d'absence en cours.</td>
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
                        <a href="{{ route('justificatif-absence.index', ['year' => 'all']) }}"
                           class="btn btn-xs {{ $selectedYear === 'all' ? 'btn-primary' : 'btn-ghost' }}">
                            Toutes
                        </a>
                        @foreach($anneesDisponibles as $annee)
                        <a href="{{ route('justificatif-absence.index', ['year' => $annee]) }}"
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
                            <th>Date</th>
                            <th>Nb jours</th>
                            <th>Certificat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($historique as $justificatif)
                        <tr class="hover opacity-80">
                            <td>du {{ $justificatif->formattedStartDate }} au {{ $justificatif->formattedEndDate }}</td>
                            <td>{{ $justificatif->nb_jours . ($justificatif->nb_jours > 1 ? ' jours' : ' jour') }}</td>
                            <td>
                                <a href="{{ asset('storage/' . $justificatif->certificat_medical) }}" target="_blank"
                                   class="btn btn-sm btn-secondary tooltip" data-tip="Voir le certificat">
                                    <i class="fa-duotone fa-file-medical"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-500">Aucun justificatif passé.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="basis-1/4">
            <livewire:justificatif-absence />
        </div>
    </div>
</div>
@endsection
