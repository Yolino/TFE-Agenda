<div x-data="{ openCreateForm: @entangle('openCreateForm'), openEditForm: @entangle('openEditForm') }">

    {{-- Barre d'actions --}}
    <div class="mb-4 flex flex-wrap gap-3 items-center justify-between">
        <button @click="openCreateForm = true" class="btn btn-success btn-sm">
            <i class="fa-solid fa-user-plus mr-2"></i>Créer un utilisateur
        </button>
        <input type="text" wire:keydown.debounce.500ms="searchNow" wire:model.defer="search"
            placeholder="Rechercher un utilisateur..."
            class="input input-bordered input-sm w-full max-w-xs" />
    </div>

    {{-- Filtres --}}
    <div x-data="{ showStatusDropdown: false, showRoleDropdown: false, showAgenceDropdown: false, statusTitle: 'Statut', roleTitle: 'Rôle', agenceTitle: 'Agence' }"
         class="mb-4 flex flex-wrap gap-2">

        <div class="relative" x-cloak>
            <button class="btn btn-info btn-sm" @click="showStatusDropdown = !showStatusDropdown" x-text="statusTitle"></button>
            <div x-show="showStatusDropdown" x-transition.opacity @click.away="showStatusDropdown = false"
                 class="absolute bg-base-100 border border-base-300 rounded-box shadow-lg mt-1 z-10 min-w-32">
                <button wire:click.prevent="filterByStatus(null)" @click="statusTitle = 'Tous'; showStatusDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Tous</button>
                <button wire:click.prevent="filterByStatus('actif')" @click="statusTitle = 'Actifs'; showStatusDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Actifs</button>
                <button wire:click.prevent="filterByStatus('inactif')" @click="statusTitle = 'Inactifs'; showStatusDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Inactifs</button>
            </div>
        </div>

        <div class="relative" x-cloak>
            <button class="btn btn-success btn-sm" @click="showRoleDropdown = !showRoleDropdown" x-text="roleTitle"></button>
            <div x-show="showRoleDropdown" x-transition.opacity @click.away="showRoleDropdown = false"
                 class="absolute bg-base-100 border border-base-300 rounded-box shadow-lg mt-1 z-10 min-w-36">
                <button wire:click.prevent="filterByIsAdmin(null)" @click="roleTitle = 'Tous'; showRoleDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Tous</button>
                <button wire:click.prevent="filterByIsAdmin('admin')" @click="roleTitle = 'Admins'; showRoleDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Admins</button>
                <button wire:click.prevent="filterByIsAdmin('user')" @click="roleTitle = 'Utilisateurs'; showRoleDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Utilisateurs</button>
            </div>
        </div>

        <div class="relative" x-cloak>
            <button class="btn btn-accent btn-sm" @click="showAgenceDropdown = !showAgenceDropdown" x-text="agenceTitle"></button>
            <div x-show="showAgenceDropdown" x-transition.opacity @click.away="showAgenceDropdown = false"
                 class="absolute bg-base-100 border border-base-300 rounded-box shadow-lg mt-1 z-10 min-w-56 max-h-72 overflow-y-auto">
                <button wire:click.prevent="filterByAgence(null)" @click="agenceTitle = 'Toutes'; showAgenceDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Toutes</button>
                @foreach($agences as $agence)
                    <button wire:click.prevent="filterByAgence({{ $agence->id }})"
                            @click="agenceTitle = '{{ addslashes($agence->display_name) }}'; showAgenceDropdown = false"
                            class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">
                        {{ $agence->display_name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto rounded-box border border-base-200 shadow-sm">
        <table class="table table-zebra w-full">
            <thead class="bg-base-200">
                <tr>
                    <th class="cursor-pointer select-none" wire:click="sortBy('name')">
                        Nom
                        @if($sortField === 'name') <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="hidden sm:table-cell cursor-pointer select-none" wire:click="sortBy('email')">
                        Email
                        @if($sortField === 'email') <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th>Rôle</th>
                    <th class="hidden md:table-cell">Département</th>
                    <th class="hidden lg:table-cell">Agence</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                @php $isLocallyActive = is_null($user->profile) || $user->profile->actif; @endphp
                <tr class="{{ !$isLocallyActive ? 'opacity-60' : '' }}">
                    <td class="font-medium">
                        {{ $user->name }} {{ $user->firstname }}
                        @if(!$isLocallyActive)
                            <span class="badge badge-warning badge-xs ml-1">Inactif</span>
                        @endif
                    </td>
                    <td class="hidden sm:table-cell">{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->is_admin() ? 'badge-error' : 'badge-info' }} badge-sm">
                            {{ $user->is_admin() ? 'Admin' : 'Util.' }}
                        </span>
                    </td>
                    <td class="hidden md:table-cell">
                        {{ $user->departements->first()?->nom ?? '—' }}
                    </td>
                    <td class="hidden lg:table-cell">
                        @forelse($user->agences as $agence)
                            <span class="badge badge-ghost badge-sm">{{ $agence->display_name }}</span>
                        @empty
                            <span class="text-base-content/40 text-xs">—</span>
                        @endforelse
                    </td>
                    <td class="text-center">
                        <button wire:click="editUser({{ $user->id }})" class="btn btn-primary btn-xs">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        @if($isLocallyActive)
                        <button type="button" x-data
                            @click="Swal.fire({
                                title: 'Désactiver ce compte ?',
                                html: 'L\'utilisateur <strong>{{ addslashes($user->firstname . ' ' . $user->name) }}</strong> sera désactivé localement uniquement.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d97706',
                                cancelButtonColor: '#6b7280',
                                confirmButtonText: 'Oui, désactiver',
                                cancelButtonText: 'Annuler',
                            }).then(r => { if (r.isConfirmed) $wire.toggleStatus({{ $user->id }}) })"
                            class="btn btn-xs btn-warning">
                            Désactiver
                        </button>
                        @else
                        <button wire:click="reactivateUser({{ $user->id }})" class="btn btn-xs btn-success">
                            <i class="fa-solid fa-rotate-right mr-1"></i>Réactiver
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- ========================= MODAL CRÉATION ========================= --}}
    <div x-show="openCreateForm" x-cloak x-transition
         class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4">
        <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-lg relative max-h-screen overflow-y-auto">
            <button @click="openCreateForm = false"
                    class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
            <div class="p-6">
                <h2 class="text-xl font-bold mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-success"></i> Créer un utilisateur
                </h2>

                <form @submit.prevent="$wire.createUser()">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nom *</span></label>
                            <input type="text" wire:model.defer="name"
                                   class="input input-bordered @error('name') input-error @enderror" />
                            @error('name') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Prénom *</span></label>
                            <input type="text" wire:model.defer="firstname"
                                   class="input input-bordered @error('firstname') input-error @enderror" />
                            @error('firstname') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Email *</span></label>
                            <input type="email" wire:model.defer="email"
                                   class="input input-bordered @error('email') input-error @enderror" />
                            @error('email') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Alias</span></label>
                            <input type="text" wire:model.defer="alias"
                                   class="input input-bordered @error('alias') input-error @enderror"
                                   placeholder="Ex: jdupont" />
                            @error('alias') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Agence *</span></label>
                            <select wire:model.defer="agence_id"
                                    class="select select-bordered @error('agence_id') select-error @enderror">
                                <option value="">Sélectionner une agence...</option>
                                @foreach($agences as $agence)
                                    <option value="{{ $agence->id }}">{{ $agence->display_name }}</option>
                                @endforeach
                            </select>
                            @error('agence_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Département *</span></label>
                            <select wire:model.defer="departement_id"
                                    class="select select-bordered @error('departement_id') select-error @enderror">
                                <option value="">Sélectionner...</option>
                                @foreach($departements as $departement)
                                    <option value="{{ $departement->id }}">{{ $departement->nom }}</option>
                                @endforeach
                            </select>
                            @error('departement_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label cursor-pointer">
                            <span class="label-text font-semibold">Administrateur</span>
                            <input type="checkbox" wire:model.defer="isAdmin" class="toggle toggle-error" />
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Téléphone</span></label>
                            <input type="tel" wire:model.defer="phone"
                                   class="input input-bordered @error('phone') input-error @enderror" />
                            @error('phone') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Fixe</span></label>
                            <input type="tel" wire:model.defer="fixe"
                                   class="input input-bordered @error('fixe') input-error @enderror" />
                            @error('fixe') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-control mb-5">
                        <label class="label"><span class="label-text">Remarque</span></label>
                        <textarea wire:model.defer="remarque" rows="2"
                                  class="textarea textarea-bordered @error('remarque') textarea-error @enderror"></textarea>
                        @error('remarque') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="openCreateForm = false" class="btn btn-ghost">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <span wire:loading wire:target="createUser"><span class="loading loading-spinner loading-sm"></span></span>
                            <i class="fa-solid fa-check" wire:loading.remove wire:target="createUser"></i>
                            Créer et envoyer l'email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========================= MODAL ÉDITION ========================= --}}
    <div x-show="openEditForm" x-cloak x-transition
         class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4">
        <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-lg relative max-h-screen overflow-y-auto">
            <button @click="openEditForm = false"
                    class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
            <div class="p-6">
                <h2 class="text-xl font-bold mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-primary"></i> Modifier l'utilisateur
                </h2>

                <form @submit.prevent="$wire.saveUser()">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nom *</span></label>
                            <input type="text" wire:model.defer="selectedUser.name"
                                   class="input input-bordered @error('selectedUser.name') input-error @enderror" />
                            @error('selectedUser.name') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Prénom *</span></label>
                            <input type="text" wire:model.defer="selectedUser.firstname"
                                   class="input input-bordered @error('selectedUser.firstname') input-error @enderror" />
                            @error('selectedUser.firstname') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text font-semibold">Email *</span></label>
                        <input type="email" wire:model.defer="selectedUser.email"
                               class="input input-bordered @error('selectedUser.email') input-error @enderror" />
                        @error('selectedUser.email') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Agence *</span></label>
                            <select wire:model.defer="selectedUser.agence_id"
                                    class="select select-bordered @error('selectedUser.agence_id') select-error @enderror">
                                <option value="">Sélectionner une agence...</option>
                                @foreach($agences as $agence)
                                    <option value="{{ $agence->id }}">{{ $agence->display_name }}</option>
                                @endforeach
                            </select>
                            @error('selectedUser.agence_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Département *</span></label>
                            <select wire:model.defer="selectedUser.departement_id"
                                    class="select select-bordered @error('selectedUser.departement_id') select-error @enderror">
                                <option value="">Sélectionner...</option>
                                @foreach($departements as $departement)
                                    <option value="{{ $departement->id }}">{{ $departement->nom }}</option>
                                @endforeach
                            </select>
                            @error('selectedUser.departement_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label cursor-pointer">
                            <span class="label-text font-semibold">Administrateur</span>
                            <input type="checkbox" wire:model.defer="selectedUser.isAdmin" class="toggle toggle-error" />
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Téléphone</span></label>
                            <input type="tel" wire:model.defer="selectedUser.phone"
                                   class="input input-bordered @error('selectedUser.phone') input-error @enderror" />
                            @error('selectedUser.phone') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Fixe</span></label>
                            <input type="tel" wire:model.defer="selectedUser.fixe"
                                   class="input input-bordered @error('selectedUser.fixe') input-error @enderror" />
                            @error('selectedUser.fixe') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-control mb-5">
                        <label class="label"><span class="label-text">Remarque</span></label>
                        <textarea wire:model.defer="selectedUser.remarque" rows="2"
                                  class="textarea textarea-bordered @error('selectedUser.remarque') textarea-error @enderror"></textarea>
                        @error('selectedUser.remarque') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="openEditForm = false" class="btn btn-ghost">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <span wire:loading wire:target="saveUser"><span class="loading loading-spinner loading-sm"></span></span>
                            <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="saveUser"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SweetAlert listener --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('swal', ({ title, text, icon }) => {
                Swal.fire({ title, text, icon, confirmButtonText: 'OK' });
            });
        });
    </script>
</div>
