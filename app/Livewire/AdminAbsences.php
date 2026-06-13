<?php

namespace App\Livewire;

use App\Models\Agence;
use App\Models\JustificatifAbsence;
use App\Models\User;
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
    public $filterYear = null;
    public $filterMonth = null;
    public ?int $filterUserId = null;
    public string $filterUserLabel = '';
    public string $userSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_directeur(), 403);
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

    public function updatedFilterYear(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMonth(): void
    {
        $this->resetPage();
    }

    public function selectUser(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $this->filterUserId = $userId;
            $this->filterUserLabel = trim($user->firstname . ' ' . $user->name);
        }
        $this->userSearch = '';
        $this->resetPage();
    }

    public function clearUserFilter(): void
    {
        $this->filterUserId = null;
        $this->filterUserLabel = '';
        $this->userSearch = '';
        $this->resetPage();
    }

    private function baseQuery(): Builder
    {
        $query = JustificatifAbsence::query()
            ->with(['user.agences.societe']);

        $this->applyTabFilter($query, $this->tab);

        // Le filtre utilisateur est indépendant : s'il est actif, il prime sur le filtre agence.
        if ($this->filterUserId !== null) {
            $query->where('user_id', $this->filterUserId);
        } elseif ($this->filterAgenceId !== null) {
            $userIds = DB::connection('bti')->table('pivot_a_u')
                ->where('agence_id', $this->filterAgenceId)
                ->pluck('user_id');
            $query->whereIn('user_id', $userIds);
        }

        if ($this->filterYear) {
            $query->whereYear('start_date', (int) $this->filterYear);
        }

        if ($this->filterMonth) {
            $query->whereMonth('start_date', (int) $this->filterMonth);
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

        $query = $this->baseQuery()->orderBy($orderColumn, $orderDir);

        // Total par agence sur l'ensemble des résultats filtrés (toutes pages confondues),
        // et non sur la seule page courante.
        $countsByAgence = (clone $query)->get()->groupBy(
            fn (JustificatifAbsence $j) => $j->user?->agences->first()?->display_name ?? 'Sans agence'
        )->map->count();

        $absences = $query
            ->paginate(10)
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

        $years = JustificatifAbsence::query()
            ->whereNotNull('start_date')
            ->selectRaw('YEAR(start_date) as yr')
            ->distinct()
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $userResults = collect();
        if (strlen(trim($this->userSearch)) >= 1) {
            $userResults = User::where('actif', true)
                ->has('agences')
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->userSearch . '%')
                      ->orWhere('firstname', 'like', '%' . $this->userSearch . '%')
                      ->orWhere('email', 'like', '%' . $this->userSearch . '%');
                })
                ->orderBy('name')
                ->limit(15)
                ->get();
        }

        return view('livewire.admin-absences', [
            'absences'         => $absences,
            'absencesByAgence' => $absencesByAgence,
            'countsByAgence'   => $countsByAgence,
            'agences'          => $agences,
            'nbEnCours'        => $this->countForTab('en_cours'),
            'nbAVenir'         => $this->countForTab('a_venir'),
            'years'            => $years,
            'userResults'      => $userResults,
        ]);
    }

    private function countForTab(string $tab): int
    {
        return $this->applyTabFilter(JustificatifAbsence::query(), $tab)->count();
    }
}
