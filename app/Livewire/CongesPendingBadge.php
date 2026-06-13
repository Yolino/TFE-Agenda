<?php

namespace App\Livewire;

use App\Models\DemandeConge;
use Livewire\Attributes\On;
use Livewire\Component;

class CongesPendingBadge extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = $this->pendingCount();
    }

    #[On('conge-decided')]
    public function refresh(): void
    {
        $this->count = $this->pendingCount();
    }

    private function pendingCount(): int
    {
        return DemandeConge::where('status', 'envoyee')->count();
    }

    public function render()
    {
        return view('livewire.conges-pending-badge');
    }
}
