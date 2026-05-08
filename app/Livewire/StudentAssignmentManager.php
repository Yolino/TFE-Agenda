<?php

namespace App\Livewire;

use App\Models\Planning;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class StudentAssignmentManager extends Component
{
    public int $week;
    public int $year;
    public ?int $agenceId = null;

    public bool $open = false;
    public ?string $departementLetter = null;

    public function mount(int $week, int $year, ?int $agenceId = null): void
    {
        $this->week = $week;
        $this->year = $year;
        $this->agenceId = $agenceId;
    }

    #[On('open-student-assignment')]
    public function openFor(string $letter): void
    {
        $this->departementLetter = $letter;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    /** Renvoie les 6 dates lundi→samedi de la semaine courante. */
    private function weekDates(): array
    {
        return collect(range(1, 6))->map(
            fn ($i) => Carbon::now()->setISODate($this->year, $this->week, $i)->toDateString()
        )->all();
    }

    /** Builder des étudiants du département dans l'agence courante. */
    private function studentsQuery(): Builder
    {
        $userIdsInAgence = $this->agenceId
            ? DB::connection('mysql')->table('agences_users')
                ->where('agence_id', $this->agenceId)
                ->pluck('user_id')
            : collect();

        return User::query()
            ->where('actif', true)
            ->where('acces_level', 'like', '%ET%')
            ->whereIn('id', $userIdsInAgence)
            ->when($this->departementLetter, fn ($q, $letter) =>
                $q->whereHas('departements', fn ($d) => $d->where('letter', $letter))
            )
            ->orderBy('name')
            ->orderBy('firstname');
    }

    public function assign(int $userId): void
    {
        $student = $this->studentsQuery()->find($userId);
        if (!$student) {
            return;
        }

        foreach ($this->weekDates() as $date) {
            Planning::firstOrCreate(
                ['user_id' => $userId, 'date' => $date],
                ['status_id' => Planning::STATUS_MAP['indisponible']]
            );
        }

        $this->dispatch('student-assignment-changed');
    }

    public function unassign(int $userId): void
    {
        Planning::where('user_id', $userId)
            ->whereIn('date', $this->weekDates())
            ->delete();

        $this->dispatch('student-assignment-changed');
    }

    public function render()
    {
        $available = collect();
        $assigned  = collect();

        if ($this->open && $this->departementLetter && $this->agenceId) {
            $assignedIds = Planning::whereIn('date', $this->weekDates())
                ->pluck('user_id')
                ->unique();

            $available = $this->studentsQuery()->whereNotIn('id', $assignedIds)->get();
            $assigned  = $this->studentsQuery()->whereIn('id', $assignedIds)->get();
        }

        return view('livewire.student-assignment-manager', [
            'available' => $available,
            'assigned'  => $assigned,
        ]);
    }
}
