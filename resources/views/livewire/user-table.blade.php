<div x-data="{ openCreateForm: @entangle('openCreateForm'), openEditForm: @entangle('openEditForm') }">
    <div>
        <div class="mb-4">
            <button @click="openCreateForm = true" class="bg-green-500 text-white px-4 py-2 rounded">Créer un utilisateur</button>
        </div>

        <div class="mb-4">
            <input type="text" wire:keydown.debounce.500ms="searchNow" wire:model.defer="search" placeholder="Rechercher un utilisateur..." class="w-full border border-gray-300 px-4 py-2 rounded">
        </div>

        <div x-data="{ showStatusDropdown: false, showRoleDropdown: false, showTypeDropdown: false, statusTitle: 'Statut', roleTitle: 'Rôle', typeTitle: 'Type' }" class="mb-4 flex space-x-4">
            <div class="relative" x-cloak>
                <button class="bg-blue-500 text-white px-4 py-2 rounded" @click="showStatusDropdown = !showStatusDropdown" x-text="statusTitle"></button>
                <div x-show="showStatusDropdown" x-transition.opacity @click.away="showStatusDropdown = false" class="absolute bg-white border rounded shadow-md mt-2">
                    <button wire:click.prevent="filterByStatus(null)" @click="statusTitle = 'Tous'; showStatusDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Tous</button>
                    <button wire:click.prevent="filterByStatus('actif')" @click="statusTitle = 'Actifs'; showStatusDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Actifs</button>
                    <button wire:click.prevent="filterByStatus('inactif')" @click="statusTitle = 'Inactifs'; showStatusDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Inactifs</button>
                </div>
            </div>

            <div class="relative" x-cloak>
                <button class="bg-green-500 text-white px-4 py-2 rounded" @click="showRoleDropdown = !showRoleDropdown" x-text="roleTitle"></button>
                <div x-show="showRoleDropdown" x-transition.opacity @click.away="showRoleDropdown = false" class="absolute bg-white border rounded shadow-md mt-2">
                    <button wire:click.prevent="filterByRole(null)" @click="roleTitle = 'Tous'; showRoleDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Tous</button>
                    <button wire:click.prevent="filterByRole('A')" @click="roleTitle = 'Admins'; showRoleDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Admins</button>
                    <button wire:click.prevent="filterByRole('U')" @click="roleTitle = 'Utilisateurs'; showRoleDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Utilisateurs</button>
                </div>
            </div>

            <div class="relative" x-cloak>
                <button class="bg-purple-500 text-white px-4 py-2 rounded" @click="showTypeDropdown = !showTypeDropdown" x-text="typeTitle"></button>
                <div x-show="showTypeDropdown" x-transition.opacity @click.away="showTypeDropdown = false" class="absolute bg-white border rounded shadow-md mt-2">
                    <button wire:click.prevent="filterByType(null)" @click="typeTitle = 'Tous'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Tous</button>
                    <button wire:click.prevent="filterByType('I')" @click="typeTitle = 'Informatique'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Informatique</button>
                    <button wire:click.prevent="filterByType('S')" @click="typeTitle = 'Secrétariat'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Secrétariat</button>
                    <button wire:click.prevent="filterByType('B')" @click="typeTitle = 'Salaire'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Salaire</button>
                    <button wire:click.prevent="filterByType('C')" @click="typeTitle = 'Comptabilité'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Comptabilité</button>
                    <button wire:click.prevent="filterByType('G')" @click="typeTitle = 'Garage'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Garage</button>
                    <button wire:click.prevent="filterByType('V')" @click="typeTitle = 'Véhicule'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Véhicule</button>
                    <button wire:click.prevent="filterByType('N')" @click="typeTitle = 'Nettoyage'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Nettoyage</button>
                    <button wire:click.prevent="filterByType('O')" @click="typeTitle = 'Ouvrier'; showTypeDropdown = false" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Ouvrier</button>
                </div>
            </div>
        </div>

        <table class="table-auto w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-4 py-2 cursor-pointer" wire:click="sortBy('name')">
                        Nom
                        @if(isset($sortField) && $sortField === 'name')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border border-gray-300 px-4 py-2 cursor-pointer" wire:click="sortBy('email')">
                        Email
                        @if(isset($sortField) && $sortField === 'email')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border border-gray-300 px-4 py-2 cursor-pointer" wire:click="sortBy('role')">
                        Rôle
                        @if(isset($sortField) && $sortField === 'role')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border border-gray-300 px-4 py-2 cursor-pointer" wire:click="sortBy('type')">
                        Type
                        @if(isset($sortField) && $sortField === 'type')
                            <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="border border-gray-300 px-4 py-2">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="border border-gray-300 px-4 py-2">{{ $user->name }} {{ $user->firstname }}</td>
                        <td class="border border-gray-300 px-4 py-2">{{ $user->email }}</td>
                        <td class="border border-gray-300 px-4 py-2">{{ $user->role }}</td>
                        <td class="border border-gray-300 px-4 py-2">
                            {{ $user->type === 'I' ? 'Informatique' : '' }}
                            {{ $user->type === 'S' ? 'Secrétariat' : '' }}
                            {{ $user->type === 'B' ? 'Salaire' : '' }}
                            {{ $user->type === 'C' ? 'Comptabilité' : '' }}
                            {{ $user->type === 'G' ? 'Garage' : '' }}
                            {{ $user->type === 'V' ? 'Véhicule' : '' }}
                            {{ $user->type === 'N' ? 'Nettoyage' : '' }}
                            {{ $user->type === 'O' ? 'Ouvrier' : '' }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center">
                            <button wire:click="editUser({{ $user->id }})" class="bg-blue-500 text-white px-4 py-2 rounded">Modifier</button>
                            <button class="bg-yellow-500 text-white px-4 py-2 rounded" wire:click="toggleStatus({{ $user->id }})">
                                {{ $user->actif ? 'Désactiver' : 'Activer' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $users->links() }}
        </div>

        <div x-show="openCreateForm" x-cloak class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" x-transition>
            <div class="bg-white rounded-lg shadow-lg p-6 w-1/3 relative">
                <button @click="openCreateForm = false" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
                    <i class="fa fa-times"></i>
                </button>
                <h2 class="text-xl font-bold mb-4">Créer un utilisateur</h2>

                <form @submit.prevent="$wire.createUser()">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700">Nom</label>
                        <input type="text" id="name" wire:model.defer="name" placeholder="Nom" class="w-full border border-gray-300 px-4 py-2 rounded">
                    </div>

                    <div class="mb-4">
                        <label for="firstname" class="block text-gray-700">Prénom</label>
                        <input type="text" id="firstname" wire:model.defer="firstname" placeholder="Prénom" class="w-full border border-gray-300 px-4 py-2 rounded">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-gray-700">Email</label>
                        <input type="email" id="email" wire:model.defer="email" placeholder="Email" class="w-full border border-gray-300 px-4 py-2 rounded">
                    </div>

                    <div class="mb-4">
                        <label for="role" class="block text-gray-700">Rôle</label>
                        <select id="role" wire:model.defer="role" class="w-full border border-gray-300 px-4 py-2 rounded">
                            <option value="">Sélectionnez un rôle</option>
                            <option value="A">Admin</option>
                            <option value="U">Utilisateur</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="type" class="block text-gray-700">Type</label>
                        <select id="type" wire:model.defer="type" class="w-full border border-gray-300 px-4 py-2 rounded">
                            <option value="">Sélectionnez un type</option>
                            <option value="I">Informatique</option>
                            <option value="S">Secrétariat</option>
                            <option value="B">Salaire</option>
                            <option value="C">Comptabilité</option>
                            <option value="G">Garage</option>
                            <option value="V">Véhicule</option>
                            <option value="N">Nettoyage</option>
                            <option value="O">Ouvrier</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="button" @click="openCreateForm = false" class="bg-gray-500 text-white px-4 py-2 rounded">Annuler</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="openEditForm" x-cloak class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" x-transition>
            <div class="bg-white rounded-lg shadow-lg p-6 w-1/3 relative">
                <button @click="openEditForm = false" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">
                    <i class="fa fa-times"></i>
                </button>
                <h2 class="text-xl font-bold mb-4">Modifier l'utilisateur</h2>

                <form @submit.prevent="$wire.saveUser()">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700">Nom</label>
                        <input type="text" id="name" wire:model.defer="selectedUser.name" placeholder="Nom" class="w-full border border-gray-300 px-4 py-2 rounded">
                    </div>

                    <div class="mb-4">
                        <label for="firstname" class="block text-gray-700">Prénom</label>
                        <input type="text" id="firstname" wire:model.defer="selectedUser.firstname" placeholder="Prénom" class="w-full border border-gray-300 px-4 py-2 rounded">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-gray-700">Email</label>
                        <input type="email" id="email" wire:model.defer="selectedUser.email" placeholder="Email" class="w-full border border-gray-300 px-4 py-2 rounded">
                    </div>

                    <div class="mb-4">
                        <label for="role" class="block text-gray-700">Rôle</label>
                        <select id="role" wire:model.defer="selectedUser.role" class="w-full border border-gray-300 px-4 py-2 rounded">
                            <option value="">Sélectionnez un rôle</option>
                            <option value="A">Admin</option>
                            <option value="U">Utilisateur</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="type" class="block text-gray-700">Type</label>
                        <select id="type" wire:model.defer="selectedUser.type" class="w-full border border-gray-300 px-4 py-2 rounded">
                            <option value="">Sélectionnez un type</option>
                            <option value="I">Informatique</option>
                            <option value="S">Secrétariat</option>
                            <option value="B">Salaire</option>
                            <option value="C">Comptabilité</option>
                            <option value="G">Garage</option>
                            <option value="V">Véhicule</option>
                            <option value="N">Nettoyage</option>
                            <option value="O">Ouvrier</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="button" @click="openEditForm = false" class="bg-gray-500 text-white px-4 py-2 rounded">Annuler</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>