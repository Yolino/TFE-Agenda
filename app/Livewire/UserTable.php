<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class UserTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $selectedUser = [
        'name' => '',
        'firstname' => '',
        'email' => '',
        'role' => '',
        'type' => '',
    ];
    public $openForm = false;
    public $openEditForm = false;
    public $openCreateForm = false;
    public $filterStatus;
    public $filterRole;
    public $filterType;

    public $name;
    public $firstname;
    public $email;
    public $role;
    public $type;

    protected $listeners = ['editUser'];

    public function mount()
    {
        $this->filterStatus = 'actif';
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function searchNow()
    {
        session(['search' => $this->search]);
        $this->resetPage();
    }

    public function edit($id)
    {
        $this->selectedUser = User::findOrFail($id)->toArray();
        $this->openForm = true;
        session()->flash('message', 'Méthode edit appelée pour l\'utilisateur ID : ' . $id);
    }

    public function openCreateForm()
    {
        $this->reset();
        $this->selectedUser = [
            'name' => '',
            'firstname' => '',
            'email' => '',
            'role' => '',
            'type' => '',
        ];
        $this->openCreateForm = true;
        $this->openEditForm = false;
    }

    public function openEditForm($id)
    {
        $this->reset();
        $this->selectedUser = User::findOrFail($id)->toArray();
        $this->openEditForm = true;
        $this->openCreateForm = false;
    }

    public function editUser($id)
    {
        $this->openEditForm($id);
    }

    public function saveUser()
    {
        $validatedData = $this->validate([
            'selectedUser.name' => 'required|string|max:255',
            'selectedUser.email' => 'required|email|unique:users,email,' . ($this->selectedUser['id'] ?? 'NULL'),
            'selectedUser.role' => 'required|string|in:A,U',
            'selectedUser.firstname' => 'required|string|max:255',
            'selectedUser.type' => 'required|string|in:I,S,B,C,G,V,N,O',
        ]);

        $user = User::findOrFail($this->selectedUser['id']);
        $user->update($validatedData['selectedUser']);
        session()->flash('message', 'Utilisateur mis à jour avec succès.');

        $this->reset('selectedUser', 'openCreateForm', 'openEditForm');
    }

    public function createUser()
    {
        $validatedData = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:A,U',
            'firstname' => 'required|string|max:255',
            'type' => 'required|string|in:I,S,B,C,G,V,N,O',
        ]);

        $validatedData['password'] = bcrypt('12345678');
        User::create($validatedData);
        session()->flash('message', 'Utilisateur créé avec succès.');

        $this->reset('name', 'firstname', 'email', 'role', 'type', 'openCreateForm');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->actif = !$user->actif;
        $user->save();

        session()->flash('message', 'Statut de l\'utilisateur mis à jour avec succès.');
    }

    public function filterByStatus($status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function filterByRole($role)
    {
        $this->filterRole = $role;
        $this->resetPage();
    }

    public function filterByType($type)
    {
        $this->filterType = $type;
        $this->resetPage();
    }

    public function render()
    {
        $query = User::query();

        if (!empty(session('search'))) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . session('search') . '%')
                  ->orWhere('firstname', 'like', '%' . session('search') . '%')
                  ->orWhere('email', 'like', '%' . session('search') . '%');
            });
        }

        if (isset($this->filterStatus)) {
            $query->where('actif', $this->filterStatus === 'actif');
        }

        if (isset($this->filterRole)) {
            $query->where('role', $this->filterRole);
        }

        if (isset($this->filterType)) {
            $query->where('type', $this->filterType);
        }

        $users = $query->orderBy($this->sortField, $this->sortDirection)->paginate(10);

        return view('livewire.user-table', [
            'users' => $users,
        ])
        ->with('sortField', $this->sortField)
        ->with('sortDirection', $this->sortDirection);
    }
}