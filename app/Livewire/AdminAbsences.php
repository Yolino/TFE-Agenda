<?php

namespace App\Livewire;

use App\Models\Agence;
use App\Models\JustificatifAbsence;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AdminAbsences extends Component
{
    use WithPagination;

    public string $tab = 'en_cours';
    public ?int $filterAgenceId = null;

    public function mount(): void
    {
        Carbon::setLocale('fr');
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['en_cours', 'a_venir', 'historique'], true) ? $tab : 'en_cours';
        $this->resetPage();
    }

    public function filterByAgence($agenceId): void
    {
        $this->filterAgenceId = $agenceId !== null ? (int) $agenceId : null;
        $this->resetPage();
    }

    private function baseQuery(): Builder
    {
        $query = JustificatifAbsence::query()
            ->with(['user.agences.societe']);

        $this->applyTabFilter($query, $this->tab);

        if ($this->filterAgenceId !== null) {
            $userIds = DB::connection('bti')->table('pivot_a_u')
                ->where('agence_id', $this->filterAgenceId)
                ->pluck('user_id');
            $query->whereIn('user_id', $userIds);
        }

        return $query;
    }

    private function applyTabFilter(Builder $query, string $tab): Builder
    {
        $today = Carbon::today()->toDateString();

        return match ($tab) {
            'a_venir'    => $query->whereDate('start_date', '>', $today),
            'historique' => $query->whereDate('end_date', '<', $today),
            default      => $query->whereDate('start_date', '<=', $today)
                                  ->whereDate('end_date', '>=', $today),
        };
    }

    public function render()
    {
        [$orderColumn, $orderDir] = match ($this->tab) {
            'historique' => ['end_date',   'desc'],
            'a_venir'    => ['start_date', 'asc'],
            default      => ['start_date', 'asc'],
        };

        $absences = $this->baseQuery()
            ->orderBy($orderColumn, $orderDir)
            ->paginate(15)
            ->through(function (JustificatifAbsence $j) {
                $j->formattedStartDate = Carbon::parse($j->start_date)->translatedFormat('d M Y');
                $j->formattedEndDate   = Carbon::parse($j->end_date)->translatedFormat('d M Y');
                return $j;
            });

        $absencesByAgence = $absences->getCollection()->groupBy(
            fn (JustificatifAbsence $j) => $j->user?->agences->first()?->display_name ?? 'Sans agence'
        );

        $agences = Agence::with('societe')
            ->where('actif', true)
            ->get()
            ->sortBy(fn ($a) => $a->display_name)
            ->values();

        return view('livewire.admin-absences', [
            'absences'         => $absences,
            'absencesByAgence' => $absencesByAgence,
            'agences'          => $agences,
            'nbEnCours'        => $this->countForTab('en_cours'),
            'nbAVenir'         => $this->countForTab('a_venir'),
        ]);
    }

    private function countForTab(string $tab): int
    {
        return $this->applyTabFilter(JustificatifAbsence::query(), $tab)->count();
    }
}
