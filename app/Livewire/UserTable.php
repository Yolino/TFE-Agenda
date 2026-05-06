<?php

namespace App\Livewire;

use App\Models\Agence;
use App\Models\Departement;
use App\Models\Planning;
use App\Models\PlanningTemplate;
use App\Models\User;
use App\Models\UserAgendaProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $selectedUser = [
        'id'             => null,
        'name'           => '',
        'firstname'      => '',
        'email'          => '',
        'phone'          => '',
        'isAdmin'        => false,
        'fixe'           => '',
        'remarque'       => '',
        'agence_id'      => null,
        'departement_id' => null,
    ];

    public $openForm = false;
    public $openEditForm = false;
    public $openCreateForm = false;

    public $filterStatus;
    public $filterIsAdmin;
    public $filterAgenceId;

    public $name;
    public $firstname;
    public $email;
    public $phone;
    public $isAdmin = false;
    public $fixe;
    public $remarque;
    public $agence_id;
    public $departement_id;

    protected $listeners = ['editUser'];

    public function mount(): void
    {
        $this->filterStatus = 'actif';
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function searchNow(): void
    {
        session(['search' => $this->search]);
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->reset(['name', 'firstname', 'email', 'phone', 'fixe', 'remarque', 'agence_id', 'departement_id']);
        $this->isAdmin = false;
        $this->openCreateForm = true;
        $this->openEditForm = false;
    }

    public function openEditForm(int $id): void
    {
        $user = User::with(['profile', 'agences', 'departements'])->findOrFail($id);

        $this->selectedUser = [
            'id'             => $user->id,
            'name'           => $user->name,
            'firstname'      => $user->firstname,
            'email'          => $user->email,
            'phone'          => $user->phone ?? '',
            'isAdmin'        => $user->is_admin(),
            'fixe'           => $user->profile?->fixe ?? '',
            'remarque'       => $user->profile?->remarque ?? '',
            'agence_id'      => $user->agences->first()?->id,
            'departement_id' => $user->departements->first()?->id,
        ];

        $this->openEditForm = true;
        $this->openCreateForm = false;
    }

    public function editUser(int $id): void
    {
        $this->openEditForm($id);
    }

    public function saveUser(): void
    {
        $validated = $this->validate([
            'selectedUser.name'           => 'required|string|max:255',
            'selectedUser.firstname'      => 'required|string|max:255',
            'selectedUser.email'          => [
                'required',
                'email',
                Rule::unique('bti.users', 'email')->ignore($this->selectedUser['id']),
            ],
            'selectedUser.phone'          => 'nullable|string|max:20',
            'selectedUser.isAdmin'        => 'boolean',
            'selectedUser.fixe'           => 'nullable|string|max:20',
            'selectedUser.remarque'       => 'nullable|string|max:500',
            'selectedUser.agence_id'      => 'required|integer|exists:bti.agences,id',
            'selectedUser.departement_id' => 'required|integer|exists:bti.departements,id',
        ]);

        $data = $validated['selectedUser'];

        $bti   = DB::connection('bti');
        $local = DB::connection('mysql');

        $bti->beginTransaction();
        $local->beginTransaction();

        try {
            $user = User::findOrFail($this->selectedUser['id']);

            $user->update([
                'name'        => $data['name'],
                'firstname'   => $data['firstname'],
                'email'       => $data['email'],
                'phone'       => $data['phone'],
                'acces_level' => $this->buildAccesLevel($user->acces_level, (bool) $data['isAdmin']),
            ]);

            UserAgendaProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'fixe'     => $data['fixe'],
                    'remarque' => $data['remarque'],
                ]
            );

            $user->agences()->sync([$data['agence_id']]);
            $user->departements()->sync([$data['departement_id']]);

            $bti->commit();
            $local->commit();
        } catch (\Throwable $e) {
            $bti->rollBack();
            $local->rollBack();
            $this->dispatch('swal',
                title: 'Erreur',
                text: 'Impossible d\'enregistrer : ' . $e->getMessage(),
                icon: 'error'
            );
            return;
        }

        $this->reset('selectedUser', 'openCreateForm', 'openEditForm');
        $this->dispatch('swal', title: 'Modifié !', text: 'Utilisateur mis à jour avec succès.', icon: 'success');
    }

    public function createUser(): void
    {
        $validated = $this->validate([
            'name'           => 'required|string|max:255',
            'firstname'      => 'required|string|max:255',
            'email'          => 'required|email|unique:bti.users,email',
            'phone'          => 'nullable|string|max:20',
            'isAdmin'        => 'boolean',
            'fixe'           => 'nullable|string|max:20',
            'remarque'       => 'nullable|string|max:500',
            'agence_id'      => 'required|integer|exists:bti.agences,id',
            'departement_id' => 'required|integer|exists:bti.departements,id',
        ]);

        $bti   = DB::connection('bti');
        $local = DB::connection('mysql');

        $bti->beginTransaction();
        $local->beginTransaction();

        $user = null;

        try {
            $user = User::create([
                'name'        => $validated['name'],
                'firstname'   => $validated['firstname'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'] ?? null,
                'password'    => bcrypt(Str::random(40)),
                'acces_level' => $validated['isAdmin'] ? 'A' : 'U',
                'actif'       => true,
            ]);

            UserAgendaProfile::create([
                'user_id'  => $user->id,
                'fixe'     => $validated['fixe'] ?? null,
                'remarque' => $validated['remarque'] ?? null,
            ]);

            $user->agences()->attach($validated['agence_id']);
            $user->departements()->attach($validated['departement_id']);

            $this->createDefaultPlanningTemplates($user);

            $bti->commit();
            $local->commit();
        } catch (\Throwable $e) {
            $bti->rollBack();
            $local->rollBack();
            $this->dispatch('swal',
                title: 'Erreur',
                text: 'Création impossible : ' . $e->getMessage(),
                icon: 'error'
            );
            return;
        }

        $emailSent = true;
        try {
            $token = Password::broker()->createToken($user);
            $user->sendNewAccountNotification($token);
        } catch (\Exception $e) {
            $emailSent = false;
        }

        $this->reset('name', 'firstname', 'email', 'phone', 'isAdmin', 'fixe', 'remarque', 'agence_id', 'departement_id', 'openCreateForm');

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

    private function buildAccesLevel(?string $current, bool $isAdmin): string
    {
        $current = (string) $current;
        $hasA = str_contains($current, 'A');

        if ($isAdmin && !$hasA) {
            return $current . 'A';
        }
        if (!$isAdmin && $hasA) {
            return str_replace('A', '', $current) ?: 'U';
        }
        return $current !== '' ? $current : 'U';
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

    public function toggleStatus(int $id): void
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

    public function filterByStatus($status): void
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function filterByIsAdmin($value): void
    {
        $this->filterIsAdmin = $value;
        $this->resetPage();
    }

    public function filterByAgence($id): void
    {
        $this->filterAgenceId = $id;
        $this->resetPage();
    }

    public function render()
    {
        $query = User::with(['departements', 'agences.societe']);

        if (!empty(session('search'))) {
            $search = session('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('firstname', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (isset($this->filterStatus)) {
            $query->where('actif', $this->filterStatus === 'actif');
        }

        if ($this->filterIsAdmin === 'admin') {
            $query->where('acces_level', 'like', '%A%');
        } elseif ($this->filterIsAdmin === 'user') {
            $query->where(function ($q) {
                $q->whereNull('acces_level')->orWhere('acces_level', 'not like', '%A%');
            });
        }

        if (isset($this->filterAgenceId)) {
            $userIds = DB::connection('mysql')->table('agences_users')
                ->where('agence_id', $this->filterAgenceId)
                ->pluck('user_id');
            $query->whereIn('id', $userIds);
        }

        $users = $query->orderBy($this->sortField, $this->sortDirection)->paginate(10);

        $agences = Agence::with('societe')
            ->where('actif', true)
            ->get()
            ->sortBy(fn ($a) => $a->display_name)
            ->values();

        $departements = Departement::where('actif', true)
            ->orderBy('letter')
            ->get();

        return view('livewire.user-table', [
            'users'        => $users,
            'agences'      => $agences,
            'departements' => $departements,
        ])
            ->with('sortField', $this->sortField)
            ->with('sortDirection', $this->sortDirection);
    }
}
