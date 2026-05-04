<?php

namespace App\Livewire;

use App\Models\Planning;
use App\Models\PlanningTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $selectedUser = [
        'name'      => '',
        'firstname' => '',
        'email'     => '',
        'role'      => '',
        'type'      => '',
        'phone'     => '',
        'fixe'      => '',
        'remarque'  => '',
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
    public $phone;
    public $fixe;
    public $remarque;

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
    }

    public function openCreateForm()
    {
        $this->reset();
        $this->selectedUser = [
            'name'      => '',
            'firstname' => '',
            'email'     => '',
            'role'      => '',
            'type'      => '',
            'phone'     => '',
            'fixe'      => '',
            'remarque'  => '',
        ];
        $this->openCreateForm = true;
        $this->openEditForm = false;
    }

    public function openEditForm($id)
    {
        $this->reset();
        $this->selectedUser = User::findOrFail($id)->toArray();
        $this->selectedUser['phone']    ??= '';
        $this->selectedUser['fixe']     ??= '';
        $this->selectedUser['remarque'] ??= '';
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
            'selectedUser.name'      => 'required|string|max:255',
            'selectedUser.email'     => 'required|email|unique:users,email,' . ($this->selectedUser['id'] ?? 'NULL'),
            'selectedUser.role'      => 'required|string|in:A,U',
            'selectedUser.firstname' => 'required|string|max:255',
            'selectedUser.type'      => 'required|string|in:I,S,B,C,G,V,N,O',
            'selectedUser.phone'     => 'nullable|string|max:20',
            'selectedUser.fixe'      => 'nullable|string|max:20',
            'selectedUser.remarque'  => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($this->selectedUser['id']);
        $user->update($validatedData['selectedUser']);

        $this->reset('selectedUser', 'openCreateForm', 'openEditForm');
        $this->dispatch('swal', title: 'Modifié !', text: 'Utilisateur mis à jour avec succès.', icon: 'success');
    }

    public function createUser()
    {
        $validatedData = $this->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'role'      => 'required|string|in:A,U',
            'firstname' => 'required|string|max:255',
            'type'      => 'required|string|in:I,S,B,C,G,V,N,O',
            'phone'     => 'nullable|string|max:20',
            'fixe'      => 'nullable|string|max:20',
            'remarque'  => 'nullable|string|max:500',
        ]);

        $validatedData['password'] = bcrypt(Str::random(40));
        $user = User::create($validatedData);

        $this->createDefaultPlanningTemplates($user);

        $emailSent = true;
        try {
            $token = Password::broker()->createToken($user);
            $user->sendNewAccountNotification($token);
        } catch (\Exception $e) {
            $emailSent = false;
        }

        $this->reset('name', 'firstname', 'email', 'role', 'type', 'phone', 'fixe', 'remarque', 'openCreateForm');

        if ($emailSent) {
            $this->dispatch('swal',
                title: 'Utilisateur créé !',
                text: "Un email de configuration a été envoyé à {$user->email}.",
                icon: 'success'
            );
        } else {
            $this->dispatch('swal',
                title: 'Attention',
                text: "Utilisateur créé, mais l'email n'a pas pu être envoyé à {$user->email}.",
                icon: 'warning'
            );
        }
    }

    private function createDefaultPlanningTemplates(User $user): void
    {
        $defaults = [
            ['day' => 1, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 2, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 3, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 4, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 5, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 6, 'status' => 'recup',  'ms' => null,    'me' => null,    'as' => null,    'ae' => null],
            ['day' => 7, 'status' => 'conge',  'ms' => null,    'me' => null,    'as' => null,    'ae' => null],
        ];

        foreach ($defaults as $t) {
            PlanningTemplate::create([
                'user_id'              => $user->id,
                'day_of_week'          => $t['day'],
                'start_time_morning'   => $t['ms'],
                'end_time_morning'     => $t['me'],
                'start_time_afternoon' => $t['as'],
                'end_time_afternoon'   => $t['ae'],
                'status_id'            => Planning::STATUS_MAP[$t['status']],
            ]);
        }
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->actif = !$user->actif;
        $user->save();

        $this->dispatch('swal',
            title: $user->actif ? 'Activé' : 'Désactivé',
            text: 'Statut de l\'utilisateur mis à jour.',
            icon: 'success'
        );
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

        return view('livewire.user-table', ['users' => $users])
            ->with('sortField', $this->sortField)
            ->with('sortDirection', $this->sortDirection);
    }
}
