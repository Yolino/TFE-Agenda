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
    <div x-data="{ showStatusDropdown: false, showRoleDropdown: false, showTypeDropdown: false, statusTitle: 'Statut', roleTitle: 'Rôle', typeTitle: 'Type' }"
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
                <button wire:click.prevent="filterByRole(null)" @click="roleTitle = 'Tous'; showRoleDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Tous</button>
                <button wire:click.prevent="filterByRole('A')" @click="roleTitle = 'Admins'; showRoleDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Admins</button>
                <button wire:click.prevent="filterByRole('U')" @click="roleTitle = 'Utilisateurs'; showRoleDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Utilisateurs</button>
            </div>
        </div>

        <div class="relative" x-cloak>
            <button class="btn btn-secondary btn-sm" @click="showTypeDropdown = !showTypeDropdown" x-text="typeTitle"></button>
            <div x-show="showTypeDropdown" x-transition.opacity @click.away="showTypeDropdown = false"
                 class="absolute bg-base-100 border border-base-300 rounded-box shadow-lg mt-1 z-10 min-w-40">
                <button wire:click.prevent="filterByType(null)" @click="typeTitle = 'Tous'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Tous</button>
                <button wire:click.prevent="filterByType('I')" @click="typeTitle = 'Informatique'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Informatique</button>
                <button wire:click.prevent="filterByType('S')" @click="typeTitle = 'Secrétariat'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Secrétariat</button>
                <button wire:click.prevent="filterByType('B')" @click="typeTitle = 'Salaire'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Salaire</button>
                <button wire:click.prevent="filterByType('C')" @click="typeTitle = 'Comptabilité'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Comptabilité</button>
                <button wire:click.prevent="filterByType('G')" @click="typeTitle = 'Garage'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Garage</button>
                <button wire:click.prevent="filterByType('V')" @click="typeTitle = 'Véhicule'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Véhicule</button>
                <button wire:click.prevent="filterByType('N')" @click="typeTitle = 'Nettoyage'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Nettoyage</button>
                <button wire:click.prevent="filterByType('O')" @click="typeTitle = 'Ouvrier'; showTypeDropdown = false" class="block w-full text-left px-4 py-2 hover:bg-base-200 text-sm">Ouvrier</button>
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
                    <th class="cursor-pointer select-none" wire:click="sortBy('email')">
                        Email
                        @if($sortField === 'email') <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="cursor-pointer select-none" wire:click="sortBy('role')">
                        Rôle
                        @if($sortField === 'role') <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="cursor-pointer select-none" wire:click="sortBy('type')">
                        Type
                        @if($sortField === 'type') <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="font-medium">{{ $user->name }} {{ $user->firstname }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->role === 'A' ? 'badge-error' : 'badge-info' }} badge-sm">
                            {{ $user->role === 'A' ? 'Admin' : 'Utilisateur' }}
                        </span>
                    </td>
                    <td>
                        @php
                        $typeLabels = ['I'=>'Informatique','S'=>'Secrétariat','B'=>'Salaire','C'=>'Comptabilité','G'=>'Garage','V'=>'Véhicule','N'=>'Nettoyage','O'=>'Ouvrier'];
                        @endphp
                        {{ $typeLabels[$user->type] ?? $user->type }}
                    </td>
                    <td class="text-center">
                        <button wire:click="editUser({{ $user->id }})" class="btn btn-primary btn-xs">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        @if($user->actif)
                        <button type="button" x-data
                            @click="Swal.fire({
                                title: 'Désactiver ce compte ?',
                                html: 'L\'utilisateur <strong>{{ addslashes($user->firstname . ' ' . $user->name) }}</strong> ne pourra plus se connecter.',
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
                        <button wire:click="toggleStatus({{ $user->id }})" class="btn btn-xs btn-success">
                            Activer
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

                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text font-semibold">Email *</span></label>
                        <input type="email" wire:model.defer="email"
                               class="input input-bordered @error('email') input-error @enderror" />
                        @error('email') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Rôle *</span></label>
                            <select wire:model.defer="role"
                                    class="select select-bordered @error('role') select-error @enderror">
                                <option value="">Sélectionner...</option>
                                <option value="A">Admin</option>
                                <option value="U">Utilisateur</option>
                            </select>
                            @error('role') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Type *</span></label>
                            <select wire:model.defer="type"
                                    class="select select-bordered @error('type') select-error @enderror">
                                <option value="">Sélectionner...</option>
                                <option value="I">Informatique</option>
                                <option value="S">Secrétariat</option>
                                <option value="B">Salaire</option>
                                <option value="C">Comptabilité</option>
                                <option value="G">Garage</option>
                                <option value="V">Véhicule</option>
                                <option value="N">Nettoyage</option>
                                <option value="O">Ouvrier</option>
                            </select>
                            @error('type') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
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
                            <label class="label"><span class="label-text font-semibold">Rôle *</span></label>
                            <select wire:model.defer="selectedUser.role"
                                    class="select select-bordered @error('selectedUser.role') select-error @enderror">
                                <option value="">Sélectionner...</option>
                                <option value="A">Admin</option>
                                <option value="U">Utilisateur</option>
                            </select>
                            @error('selectedUser.role') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Type *</span></label>
                            <select wire:model.defer="selectedUser.type"
                                    class="select select-bordered @error('selectedUser.type') select-error @enderror">
                                <option value="">Sélectionner...</option>
                                <option value="I">Informatique</option>
                                <option value="S">Secrétariat</option>
                                <option value="B">Salaire</option>
                                <option value="C">Comptabilité</option>
                                <option value="G">Garage</option>
                                <option value="V">Véhicule</option>
                                <option value="N">Nettoyage</option>
                                <option value="O">Ouvrier</option>
                            </select>
                            @error('selectedUser.type') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
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
