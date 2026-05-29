<?php

namespace App\Livewire;

use App\Models\Agence;
use App\Models\Departement;
use App\Models\Planning;
use App\Models\PlanningTemplate;
use App\Models\User;
use App\Models\UserAgendaProfile;
use App\Services\ActivityLogger;
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
        'isEtudiant'     => false,
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
    public $alias;
    public $email;
    public $phone;
    public $isAdmin = false;
    public $isEtudiant = false;
    public $fixe;
    public $remarque;
    public $agence_id;
    public $departement_id;

    protected $listeners = ['editUser'];

    public function mount(): void
    {
        $this->filterStatus = 'actif';
        session()->forget('search');
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
        $this->reset(['name', 'firstname', 'alias', 'email', 'phone', 'fixe', 'remarque', 'agence_id', 'departement_id']);
        $this->isAdmin = false;
        $this->isEtudiant = false;
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
            'isEtudiant'     => $user->is_etudiant(),
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
            'selectedUser.isEtudiant'     => 'boolean',
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

            // Affectations AVANT modification : les pivots (agence/département) ne
            // déclenchent aucun événement Eloquent, on capture donc l'ancien état ici.
            $oldAgenceId      = $user->agences()->first()?->id;
            $oldDepartementId = $user->departements()->first()?->id;

            $user->update([
                'name'        => $data['name'],
                'firstname'   => $data['firstname'],
                'email'       => $data['email'],
                'phone'       => $data['phone'],
                'acces_level' => $this->buildAccesLevel((bool) $data['isEtudiant']),
            ]);

            UserAgendaProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'fixe'     => $data['fixe'],
                    'remarque' => $data['remarque'],
                    'is_admin' => (bool) $data['isAdmin'],
                ]
            );

            $user->agences()->sync([$data['agence_id']]);
            $user->departements()->sync([$data['departement_id']]);

            // Trace les changements d'affectation sur la base BTI (tables pivot).
            $this->logBtiRelationChange($user, 'agence', $oldAgenceId, $data['agence_id']);
            $this->logBtiRelationChange($user, 'departement', $oldDepartementId, $data['departement_id']);

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
            'alias'          => 'nullable|string|max:50',
            'email'          => 'required|email|unique:bti.users,email',
            'phone'          => 'nullable|string|max:20',
            'isAdmin'        => 'boolean',
            'isEtudiant'     => 'boolean',
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
                'alias'       => $validated['alias'] ?? null,
                'email'       => $validated['email'],
                'phone'       => $validated['phone'] ?? null,
                'password'    => bcrypt(Str::random(40)),
                'acces_level' => $this->buildAccesLevel((bool) ($validated['isEtudiant'] ?? false)),
                'actif'       => true,
            ]);

            UserAgendaProfile::create([
                'user_id'  => $user->id,
                'fixe'     => $validated['fixe'] ?? null,
                'remarque' => $validated['remarque'] ?? null,
                'is_admin' => (bool) ($validated['isAdmin'] ?? false),
            ]);

            $user->agences()->attach($validated['agence_id']);
            $user->departements()->attach($validated['departement_id']);

            // Trace l'affectation initiale (agence/département) sur la base BTI.
            $this->logBtiRelationChange($user, 'agence', null, $validated['agence_id']);
            $this->logBtiRelationChange($user, 'departement', null, $validated['departement_id']);

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

        $this->reset('name', 'firstname', 'alias', 'email', 'phone', 'isAdmin', 'isEtudiant', 'fixe', 'remarque', 'agence_id', 'departement_id', 'openCreateForm');

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

    private function buildAccesLevel(bool $isEtudiant): string
    {
        return $isEtudiant ? User::ROLE_ETUDIANT : 'U';
    }

    /**
     * Journalise un changement d'affectation (agence / département) sur la base BTI.
     * Ces liens sont portés par des tables pivot : les événements Eloquent ne s'y
     * déclenchent pas, on les trace donc explicitement (préfixe "bti." => surlignage).
     */
    private function logBtiRelationChange(User $user, string $relation, $oldId, $newId): void
    {
        if ((int) $oldId === (int) $newId) {
            return;
        }

        ActivityLogger::record("bti.user.{$relation}_changed", $user, [
            'avant' => $oldId !== null ? (int) $oldId : null,
            'apres' => (int) $newId,
        ], "Changement de {$relation} d'un utilisateur (base BTI)");
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
        $profile = UserAgendaProfile::firstOrCreate(
            ['user_id' => $id],
            ['actif' => true]
        );
        $profile->actif = false;
        $profile->save();

        $this->dispatch('swal',
            title: 'Désactivé',
            text: 'Le compte a été désactivé localement. La base globale n\'a pas été modifiée.',
            icon: 'success'
        );
    }

    public function reactivateUser(int $id): void
    {
        $profile = UserAgendaProfile::firstOrCreate(
            ['user_id' => $id],
            ['actif' => true]
        );
        $profile->actif = true;
        $profile->save();

        $this->dispatch('swal',
            title: 'Réactivé',
            text: 'Le compte a été réactivé avec succès.',
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
        $query = User::with(['departements', 'agences.societe', 'profile']);

        if (!empty(session('search'))) {
            $search = session('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('firstname', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (isset($this->filterStatus)) {
            $inactiveIds = DB::connection('mysql')
                ->table('user_agenda_profiles')
                ->where('actif', false)
                ->pluck('user_id');

            if ($this->filterStatus === 'inactif') {
                $query->whereIn('id', $inactiveIds);
            } elseif ($this->filterStatus === 'actif') {
                $query->whereNotIn('id', $inactiveIds);
            }
        }

        if ($this->filterIsAdmin === 'admin') {
            $adminIds = DB::connection('mysql')
                ->table('user_agenda_profiles')
                ->where('is_admin', true)
                ->pluck('user_id');
            $query->whereIn('id', $adminIds);
        } elseif ($this->filterIsAdmin === 'user') {
            $adminIds = DB::connection('mysql')
                ->table('user_agenda_profiles')
                ->where('is_admin', true)
                ->pluck('user_id');
            $query->whereNotIn('id', $adminIds);
        }

        if (isset($this->filterAgenceId)) {
            $userIds = DB::connection('bti')->table('pivot_a_u')
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
