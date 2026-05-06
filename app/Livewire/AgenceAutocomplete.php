<?php

namespace App\Livewire;

use App\Models\Agence;
use Livewire\Component;

class AgenceAutocomplete extends Component
{
    public int $week;
    public int $year;
    public string $search = '';

    public function mount(?int $week = null, ?int $year = null): void
    {
        $this->week = $week ?? (int) now()->format('W');
        $this->year = $year ?? (int) now()->year;
    }

    public function select(int $agenceId)
    {
        return redirect()->route('planning', [
            'agence_id' => $agenceId,
            'week'      => $this->week,
            'year'      => $this->year,
        ]);
    }

    public function render()
    {
        $agences = collect();

        if (strlen($this->search) >= 1) {
            $agences = Agence::with('societe')
                ->where('actif', true)
                ->where(function ($q) {
                    $q->where('alias', 'like', '%' . $this->search . '%')
                      ->orWhere('localite', 'like', '%' . $this->search . '%');
                })
                ->limit(15)
                ->get()
                ->sortBy(fn ($a) => $a->display_name)
                ->values();
        }

        return view('livewire.agence-autocomplete', [
            'agences' => $agences,
        ]);
    }
}
