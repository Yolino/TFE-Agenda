<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Agence;
use App\Models\DemandeConge;
use App\Models\Planning;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminConges extends Component
{
    public $selectedConge = null;
    public $filter = 'envoyee';
    public ?int $filterAgenceId = null;

    public function selectConge($id)
    {
        $this->selectedConge = $this->selectedConge === $id ? null : $id;
    }

    public function filterByAgence($agenceId): void
    {
        $this->filterAgenceId = $agenceId !== null ? (int) $agenceId : null;
    }

    public function accepter($id, LeaveBalanceService $leaveBalance)
    {
        $conge = DemandeConge::with('user')->where('id', $id)->where('status', 'envoyee')->firstOrFail();
        $conge->update([
            'status' => 'acceptee',
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        // Mettre à jour le planning avec le type de congé correspondant
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

        $this->selectedConge = null;

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

        $this->selectedConge = null;

        session()->flash('success', 'La demande de congé a été refusée.');
    }

    public function render()
    {
        Carbon::setLocale('fr');

        $query = DemandeConge::with(['user.agences.societe', 'decidedBy', 'cancelledBy'])->orderBy('created_at', 'desc');

        if ($this->filter !== 'toutes') {
            $query->where('status', $this->filter);
        }

        if ($this->filterAgenceId !== null) {
            $userIds = DB::connection('bti')->table('pivot_a_u')
                ->where('agence_id', $this->filterAgenceId)
                ->pluck('user_id');
            $query->whereIn('user_id', $userIds);
        }

        $demandes = $query->get()->map(function ($conge) {
            $conge->formattedStartDate = Carbon::parse($conge->start_date)->translatedFormat('d M Y');
            $conge->formattedEndDate = Carbon::parse($conge->end_date)->translatedFormat('d M Y');
            return $conge;
        });

        $demandesByAgence = $demandes->groupBy(
            fn ($conge) => $conge->user?->agences->first()?->display_name ?? 'Sans agence'
        );

        $agences = Agence::with('societe')
            ->where('actif', true)
            ->get()
            ->sortBy(fn ($a) => $a->display_name)
            ->values();

        $nbEnAttente = DemandeConge::where('status', 'envoyee')->count();

        return view('livewire.admin-conges', [
            'demandes' => $demandes,
            'demandesByAgence' => $demandesByAgence,
            'agences' => $agences,
            'nbEnAttente' => $nbEnAttente,
        ]);
    }
}
