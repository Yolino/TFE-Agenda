<div>
    <dialog class="modal {{ $open ? 'modal-open' : '' }}">
        <div class="modal-box max-w-3xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg">
                    <i class="fa-solid fa-user-graduate text-indigo-500 mr-1"></i>
                    Étudiants
                    <span class="text-sm font-normal text-base-content/60 ml-2">Sem. {{ $week }} / {{ $year }}</span>
                </h3>
                <button wire:click="close" class="btn btn-sm btn-ghost btn-circle">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide text-base-content/70 mb-2">
                        Disponibles
                    </h4>
                    <div class="border rounded-lg p-2 max-h-80 overflow-y-auto space-y-1 bg-base-200/40 min-h-[6rem]">
                        @forelse($available as $student)
                            <div class="flex items-center justify-between p-2 bg-base-100 rounded hover:bg-base-200 transition">
                                <span class="text-sm">
                                    <i class="fa-solid fa-user-graduate text-indigo-500 text-xs mr-1"></i>
                                    {{ $student->name }} {{ $student->firstname }}
                                </span>
                                <button wire:click="assign({{ $student->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="assign({{ $student->id }})"
                                        class="btn btn-xs btn-success tooltip tooltip-left" data-tip="Assigner cette semaine">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        @empty
                            <p class="text-xs text-base-content/50 italic text-center p-4">
                                Aucun étudiant disponible.
                            </p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide text-base-content/70 mb-2">
                        Assignés cette semaine
                    </h4>
                    <div class="border rounded-lg p-2 max-h-80 overflow-y-auto space-y-1 bg-base-200/40 min-h-[6rem]">
                        @forelse($assigned as $student)
                            <div class="flex items-center justify-between p-2 bg-base-100 rounded hover:bg-base-200 transition">
                                <span class="text-sm">
                                    <i class="fa-solid fa-user-graduate text-indigo-500 text-xs mr-1"></i>
                                    {{ $student->name }} {{ $student->firstname }}
                                </span>
                                <button type="button"
                                        @click="Swal.fire({
                                            title: 'Désassigner cet étudiant ?',
                                            html: 'Toutes ses entrées de la semaine seront <strong>supprimées</strong>.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonText: 'Oui, désassigner',
                                            cancelButtonText: 'Annuler',
                                            buttonsStyling: false,
                                            customClass: { confirmButton: 'btn btn-error', cancelButton: 'btn btn-neutral ml-3' },
                                        }).then((r) => { if (r.isConfirmed) $wire.unassign({{ $student->id }}) })"
                                        wire:loading.attr="disabled"
                                        wire:target="unassign({{ $student->id }})"
                                        class="btn btn-xs btn-error tooltip tooltip-left" data-tip="Désassigner">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        @empty
                            <p class="text-xs text-base-content/50 italic text-center p-4">
                                Aucun étudiant assigné.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="modal-action">
                <button wire:click="close" class="btn">Fermer</button>
            </div>
        </div>
        <div class="modal-backdrop bg-black/40" wire:click="close"></div>
    </dialog>
</div>
