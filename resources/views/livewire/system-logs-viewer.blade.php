<div>
    {{-- ============================ ONGLETS ============================ --}}
    <div role="tablist" class="tabs tabs-boxed mb-4 w-fit">
        <button role="tab" wire:click="$set('tab', 'metier')"
                class="tab {{ $tab === 'metier' ? 'tab-active' : '' }}">
            <i class="fa-solid fa-user-shield mr-2"></i>Logs métiers
        </button>
        <button role="tab" wire:click="$set('tab', 'technique')"
                class="tab {{ $tab === 'technique' ? 'tab-active' : '' }}">
            <i class="fa-solid fa-bug mr-2"></i>Logs techniques
        </button>
    </div>

    {{-- ===================================================================== --}}
    {{--  ONGLET 1 : LOGS MÉTIERS (base de données, filtrables)                --}}
    {{-- ===================================================================== --}}
    @if($tab === 'metier')
        {{-- Barre de filtres --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs">Recherche</span></label>
                <input type="text" wire:model.live.debounce.400ms="search"
                       placeholder="Action, auteur, description..."
                       class="input input-bordered input-sm w-full" />
            </div>

            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs">Utilisateur</span></label>
                <select wire:model.live="filterUser" class="select select-bordered select-sm w-full">
                    <option value="">Tous</option>
                    @foreach($users as $u)
                        <option value="{{ $u->user_id }}">{{ $u->user_name ?? 'Utilisateur #' . $u->user_id }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs">Type d'action</span></label>
                <select wire:model.live="filterAction" class="select select-bordered select-sm w-full">
                    <option value="">Toutes</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs">Du</span></label>
                <input type="date" wire:model.live="dateFrom" class="input input-bordered input-sm w-full" />
            </div>

            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs">Au</span></label>
                <input type="date" wire:model.live="dateTo" class="input input-bordered input-sm w-full" />
            </div>
        </div>

        <div class="flex items-center justify-between mb-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm text-base-content/60">{{ $logs->total() }} entrée(s)</span>
                {{-- Légende de la mise en évidence des écritures sur la base globale BTI --}}
                <span class="text-xs flex items-center gap-1 text-base-content/50">
                    <span class="badge badge-warning badge-xs">BTI</span> = écriture sur la base globale
                </span>
            </div>
            <button wire:click="resetFilters" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-eraser mr-1"></i>Réinitialiser
            </button>
        </div>

        {{-- Tableau --}}
        <div class="overflow-x-auto rounded-box border border-base-200 shadow-sm">
            <table class="table table-zebra table-sm w-full">
                <thead class="bg-base-200">
                    <tr>
                        <th>Date</th>
                        <th>Auteur</th>
                        <th>Action</th>
                        <th class="hidden md:table-cell">Sujet</th>
                        <th class="hidden lg:table-cell">Description</th>
                        <th class="text-center">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        {{-- Les écritures sur la base BTI sont mises en évidence (bordure + fond) --}}
                        <tr x-data="{ open: false }" class="{{ $log->is_bti ? 'border-l-4 border-warning bg-warning/5' : '' }}">
                            <td class="whitespace-nowrap text-xs">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="font-medium">
                                {{ $log->user_name ?? '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $log->badge_class }} badge-sm whitespace-nowrap">
                                    {{ $log->action }}
                                </span>
                                @if($log->is_bti)
                                    <span class="badge badge-warning badge-xs ml-1" title="Écriture sur la base globale BTI">BTI</span>
                                @endif
                            </td>
                            <td class="hidden md:table-cell text-xs">
                                {{ $log->subject_label ?? '—' }}
                            </td>
                            <td class="hidden lg:table-cell text-xs">
                                {{ $log->description ?? '—' }}
                            </td>
                            <td class="text-center">
                                @if(!empty($log->properties))
                                    <button @click="open = !open" class="btn btn-ghost btn-xs">
                                        <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                @else
                                    <span class="text-base-content/30">—</span>
                                @endif
                            </td>
                        </tr>
                        @if(!empty($log->properties))
                            <tr x-show="open" x-cloak>
                                <td colspan="6" class="bg-base-200/50">
                                    <pre class="text-xs whitespace-pre-wrap break-all p-2">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-base-content/50">
                                <i class="fa-solid fa-inbox text-2xl mb-2 block"></i>
                                Aucun log métier ne correspond aux filtres.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>

    {{-- ===================================================================== --}}
    {{--  ONGLET 2 : LOGS TECHNIQUES (fichier journalier, 30 jours)            --}}
    {{-- ===================================================================== --}}
    @else
        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <div class="form-control w-full max-w-xs">
                <label class="label py-1"><span class="label-text text-xs">Niveau</span></label>
                <select wire:model.live="filterLevel" class="select select-bordered select-sm w-full">
                    <option value="">Tous les niveaux</option>
                    <option value="ERROR">ERROR</option>
                    <option value="WARNING">WARNING</option>
                    <option value="CRITICAL">CRITICAL</option>
                    <option value="INFO">INFO</option>
                    <option value="DEBUG">DEBUG</option>
                </select>
            </div>
            <div class="text-xs text-base-content/60">
                @if($techFile)
                    <i class="fa-solid fa-file-lines mr-1"></i>{{ basename($techFile) }}
                    <span class="ml-2">({{ count($techLogs) }} entrée(s) — {{ $techLines }} dernières lignes)</span>
                @endif
            </div>
        </div>

        @if(!$techFile)
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info"></i>
                <span>Aucun fichier de log technique pour le moment (rien n'a encore été journalisé sur le canal "technique").</span>
            </div>
        @else
            <div class="overflow-x-auto rounded-box border border-base-200 shadow-sm">
                <table class="table table-zebra table-sm w-full">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="w-44">Date</th>
                            <th class="w-28">Niveau</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($techLogs as $entry)
                            <tr>
                                <td class="whitespace-nowrap text-xs align-top">{{ $entry['date'] }}</td>
                                <td class="align-top">
                                    <span class="badge {{ $this->levelBadge($entry['level']) }} badge-sm">
                                        {{ $entry['level'] }}
                                    </span>
                                </td>
                                <td class="text-xs">
                                    <pre class="whitespace-pre-wrap break-all font-agenda">{{ $entry['message'] }}</pre>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-8 text-base-content/50">
                                    <i class="fa-solid fa-check-circle text-2xl mb-2 block text-success"></i>
                                    Aucune entrée technique pour ce filtre.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
