<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserAgendaProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditProfile extends Component
{
    public bool $showModal = false;

    public string $firstname = '';
    public string $name      = '';
    public string $email     = '';
    public ?string $phone    = null;
    public ?string $fixe     = null;
    public ?string $remarque = null;

    public function mount(): void
    {
        $this->loadFromUser();
    }

    public function openModal(): void
    {
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
        $this->loadFromUser();
    }

    public function save(): void
    {
        $user = User::with('profile')->findOrFail(auth()->id());

        $this->validate([
            'firstname' => 'required|string|max:50',
            'name'      => 'required|string|max:50',
            'email'     => ['required', 'email', 'max:100', Rule::unique('bti.users', 'email')->ignore($user->id)],
            'phone'     => 'nullable|string|max:20',
            'fixe'      => 'nullable|string|max:20',
            'remarque'  => 'nullable|string|max:500',
        ], [
            'firstname.required' => 'Le prénom est requis.',
            'firstname.max'      => 'Le prénom ne peut pas dépasser 50 caractères.',
            'name.required'      => 'Le nom est requis.',
            'name.max'           => 'Le nom ne peut pas dépasser 50 caractères.',
            'email.required'     => "L'email est requis.",
            'email.email'        => "L'email doit être une adresse valide.",
            'email.unique'       => "Cette adresse email est déjà utilisée.",
        ]);

        $bti   = DB::connection('bti');
        $local = DB::connection('mysql');

        $bti->beginTransaction();
        $local->beginTransaction();

        try {
            $user->update([
                'firstname' => $this->firstname,
                'name'      => $this->name,
                'email'     => $this->email,
                'phone'     => $this->phone,
            ]);

            UserAgendaProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'fixe'     => $this->fixe,
                    'remarque' => $this->remarque,
                ]
            );

            $bti->commit();
            $local->commit();
        } catch (\Throwable $e) {
            $bti->rollBack();
            $local->rollBack();
            $this->dispatch('swal',
                title: 'Erreur',
                text: 'Impossible de mettre à jour le profil : ' . $e->getMessage(),
                icon: 'error'
            );
            return;
        }

        $this->showModal = false;
        $this->dispatch('swal', title: 'Profil mis à jour !', text: 'Vos informations ont été sauvegardées.', icon: 'success');
    }

    public function sendPasswordReset(): void
    {
        $status = Password::sendResetLink(['email' => auth()->user()->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->dispatch('swal',
                title: 'Email envoyé !',
                text: 'Un lien de réinitialisation a été envoyé à ' . auth()->user()->email . '.',
                icon: 'success'
            );
        } else {
            $this->dispatch('swal',
                title: 'Erreur',
                text: __($status),
                icon: 'error'
            );
        }
    }

    private function loadFromUser(): void
    {
        $user = User::with('profile')->findOrFail(auth()->id());

        $this->firstname = $user->firstname;
        $this->name      = $user->name;
        $this->email     = $user->email;
        $this->phone     = $user->phone;
        $this->fixe      = $user->profile?->fixe;
        $this->remarque  = $user->profile?->remarque;
    }

    public function render()
    {
        return view('livewire.edit-profile');
    }
}
