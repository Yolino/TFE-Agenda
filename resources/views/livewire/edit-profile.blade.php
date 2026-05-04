<div>
    {{-- Infos profil + boutons d'action --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 mb-6">
        <div>
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-0.5">Prénom</p>
            <p class="text-base font-medium">{{ $firstname }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-0.5">Nom</p>
            <p class="text-base font-medium">{{ $name }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-0.5">Email</p>
            <p class="text-base font-medium">{{ $email }}</p>
        </div>
        @if($phone)
        <div>
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-0.5">Téléphone</p>
            <p class="text-base font-medium">{{ $phone }}</p>
        </div>
        @endif
        @if($fixe)
        <div>
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-0.5">Fixe</p>
            <p class="text-base font-medium">{{ $fixe }}</p>
        </div>
        @endif
        @if($remarque)
        <div class="md:col-span-2">
            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-0.5">Remarque</p>
            <p class="text-base font-medium">{{ $remarque }}</p>
        </div>
        @endif
    </div>

    {{-- Boutons --}}
    <div class="flex flex-wrap gap-3">
        <button wire:click="openModal" class="btn btn-primary">
            <i class="fa-solid fa-pen-to-square mr-2"></i>
            Modifier mon profil
        </button>
        <button wire:click="sendPasswordReset" class="btn btn-outline btn-warning"
                wire:loading.attr="disabled" wire:target="sendPasswordReset">
            <span wire:loading wire:target="sendPasswordReset">
                <span class="loading loading-spinner loading-sm"></span>
            </span>
            <i class="fa-solid fa-key mr-2" wire:loading.remove wire:target="sendPasswordReset"></i>
            Réinitialiser le mot de passe
        </button>
    </div>

    {{-- Modale édition profil --}}
    <div x-data="{ open: @entangle('showModal') }">
        <div x-show="open" x-cloak x-transition
             class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4">
            <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-lg relative">
                <button wire:click="closeModal"
                        class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-5 flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-primary"></i>
                        Modifier mon profil
                    </h3>

                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">Prénom *</span>
                                </label>
                                <input type="text" wire:model="firstname"
                                       class="input input-bordered @error('firstname') input-error @enderror" />
                                @error('firstname')
                                    <span class="text-error text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">Nom *</span>
                                </label>
                                <input type="text" wire:model="name"
                                       class="input input-bordered @error('name') input-error @enderror" />
                                @error('name')
                                    <span class="text-error text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold">Email *</span>
                            </label>
                            <input type="email" wire:model="email"
                                   class="input input-bordered @error('email') input-error @enderror" />
                            @error('email')
                                <span class="text-error text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Téléphone mobile</span>
                                </label>
                                <input type="tel" wire:model="phone" 
                                       class="input input-bordered @error('phone') input-error @enderror" />
                                @error('phone')
                                    <span class="text-error text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Fixe</span>
                                </label>
                                <input type="tel" wire:model="fixe" 
                                       class="input input-bordered @error('fixe') input-error @enderror" />
                                @error('fixe')
                                    <span class="text-error text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Remarque</span>
                            </label>
                            <textarea wire:model="remarque" rows="3"
                                      class="textarea textarea-bordered @error('remarque') textarea-error @enderror"
                                      placeholder="Informations supplémentaires..."></textarea>
                            @error('remarque')
                                <span class="text-error text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closeModal" class="btn btn-ghost">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading wire:target="save">
                                    <span class="loading loading-spinner loading-sm"></span>
                                </span>
                                <i class="fa-solid fa-floppy-disk mr-1" wire:loading.remove wire:target="save"></i>
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- SweetAlert listener (une seule instance par page) --}}
    @once
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('swal', ({ title, text, icon }) => {
                Swal.fire({ title, text, icon, confirmButtonText: 'OK' });
            });
        });
    </script>
    @endonce
</div>
