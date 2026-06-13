<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Agence;
use App\Models\DemandeConge;
use App\Models\Planning;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class AdminConges extends Component
{
    use WithPagination;

    public $selectedConge = null;
    public $filter = 'envoyee';
    public ?int $filterAgenceId = null;
    public $filterYear = null;
    public $filterMonth = null;
    public ?int $filterUserId = null;
    public string $filterUserLabel = '';
    public string $userSearch = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_directeur(), 403);
    }

    public function selectConge($id)
    {
        $this->selectedConge = $this->selectedConge === $id ? null : $id;
    }

    public function filterByAgence($agenceId): void
    {
        $this->filterAgenceId = $agenceId !== null ? (int) $agenceId : null;
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
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

    public function accepter($id, LeaveBalanceService $leaveBalance)
    {
        $conge = DemandeConge::with('user')->where('id', $id)->where('status', 'envoyee')->firstOrFail();
        $conge->update([
            'status' => 'acceptee',
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        $statusMap = [
            'conge' => 'conge',
            'recup' => 'recup',
            'css' => 'css',
        ];
        $planningStatus = $statusMap[$conge->type] ?? 'conge';

        $workingDates = $leaveBalance->workingDatesBetween(
            $conge->user,
            Carbon::parse($conge->start_date),
            Carbon::parse($conge->end_date)
        );

        foreach ($workingDates as $date) {
            Planning::updateOrCreate(
                [
                    'user_id' => $conge->user_id,
                    'date' => $date->format('Y-m-d'),
                ],
                [
                    'status_id' => Planning::STATUS_MAP[$planningStatus],
                    'demande_conge_id' => $conge->id,
                    'start_time_morning' => null,
                    'end_time_morning' => null,
                    'start_time_afternoon' => null,
                    'end_time_afternoon' => null,
                ]
            );
        }

        ActivityLogger::record('conge.accepted', $conge, [
            'owner_user_id' => $conge->user_id,
            'decided_by'    => auth()->id(),
            'type'          => $conge->type,
            'start_date'    => (string) $conge->start_date,
            'end_date'      => (string) $conge->end_date,
        ], 'Demande de congé acceptée');

        $this->selectedConge = null;

        $this->dispatch('conge-decided');

        session()->flash('success', 'La demande de congé a été acceptée et le planning mis à jour.');
    }

    public function refuser($id)
    {
        $conge = DemandeConge::where('id', $id)->where('status', 'envoyee')->firstOrFail();
        $conge->update([
            'status' => 'refusee',
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        ActivityLogger::record('conge.refused', $conge, [
            'owner_user_id' => $conge->user_id,
            'decided_by'    => auth()->id(),
            'type'          => $conge->type,
            'start_date'    => (string) $conge->start_date,
            'end_date'      => (string) $conge->end_date,
        ], 'Demande de congé refusée');

        $this->selectedConge = null;

        $this->dispatch('conge-decided');

        session()->flash('success', 'La demande de congé a été refusée.');
    }

    public function render()
    {
        Carbon::setLocale('fr');

        $query = DemandeConge::with(['user.agences.societe', 'decidedBy', 'cancelledBy'])->orderBy('created_at', 'desc');

        if ($this->filter !== 'toutes') {
            $query->where('status', $this->filter);
        }

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

        // Total par agence sur l'ensemble des résultats filtrés (toutes pages confondues),
        // et non sur la seule page courante.
        $countsByAgence = (clone $query)->get()->groupBy(
            fn ($conge) => $conge->user?->agences->first()?->display_name ?? 'Sans agence'
        )->map->count();

        $demandes = $query->paginate(10)->through(function ($conge) {
            $conge->formattedStartDate = Carbon::parse($conge->start_date)->translatedFormat('d M Y');
            $conge->formattedEndDate = Carbon::parse($conge->end_date)->translatedFormat('d M Y');
            return $conge;
        });

        $demandesByAgence = $demandes->getCollection()->groupBy(
            fn ($conge) => $conge->user?->agences->first()?->display_name ?? 'Sans agence'
        );

        $agences = Agence::with('societe')
            ->where('actif', true)
            ->get()
            ->sortBy(fn ($a) => $a->display_name)
            ->values();

        $nbEnAttente = DemandeConge::where('status', 'envoyee')->count();

        $years = DemandeConge::query()
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

        return view('livewire.admin-conges', [
            'demandes' => $demandes,
            'demandesByAgence' => $demandesByAgence,
            'countsByAgence' => $countsByAgence,
            'agences' => $agences,
            'nbEnAttente' => $nbEnAttente,
            'years' => $years,
            'userResults' => $userResults,
        ]);
    }
}
