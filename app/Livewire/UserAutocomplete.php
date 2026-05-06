<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class UserAutocomplete extends Component
{
    public ?int $selectedUserId = null;
    public string $currentLabel = '';
    public string $search = '';

    public function mount(?int $selectedUserId = null): void
    {
        $this->selectedUserId = $selectedUserId;

        if ($selectedUserId) {
            $u = User::find($selectedUserId);
            $this->currentLabel = $u ? trim($u->firstname . ' ' . $u->name) : '';
        }
    }

    public function select(int $userId)
    {
        return redirect('/mon-planning?user_id=' . $userId);
    }

    public function render()
    {
        $users = collect();

        if (strlen($this->search) >= 1) {
            $users = User::where('actif', true)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orderBy('name')
                ->limit(15)
                ->get();
        }

        return view('livewire.user-autocomplete', [
            'users' => $users,
        ]);
    }
}
