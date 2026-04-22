<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Requests\CongeRequest;
use App\Models\DemandeConge;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use PDF;

class CongeController extends Controller
{
    public function __construct(private LeaveBalanceService $leaveBalance)
    {
    }

    public function index(Request $request): View
    {
        Carbon::setLocale('fr');

        $today = Carbon::today();
        $user = auth()->user();

        $format = fn ($conge) => tap($conge, function ($c) {
            $c->formattedStartDate = Carbon::parse($c->start_date)->translatedFormat('d M Y');
            $c->formattedEndDate   = Carbon::parse($c->end_date)->translatedFormat('d M Y');
        });

        $conges = DemandeConge::with('decidedBy')
            ->where('user_id', $user->id)
            ->where('end_date', '>=', $today)
            ->orderBy('start_date')
            ->get()
            ->map($format);

        $anneesDisponibles = DemandeConge::where('user_id', $user->id)
            ->where('end_date', '<', $today)
            ->selectRaw('YEAR(end_date) as annee')
            ->distinct()
            ->orderBy('annee', 'desc')
            ->pluck('annee');

        $selectedYear = $request->query('year', (string) $today->year);

        $historiqueQuery = DemandeConge::with('decidedBy')
            ->where('user_id', $user->id)
            ->where('end_date', '<', $today);

        if ($selectedYear !== 'all') {
            $historiqueQuery->whereYear('end_date', $selectedYear);
        }

        $historique = $historiqueQuery
            ->orderBy('start_date', 'desc')
            ->get()
            ->map($format);

        $currentYear = (int) $today->year;
        $balance = [
            'base'      => LeaveBalanceService::BASE_ANNUAL_VA,
            'used'      => $this->leaveBalance->getUsedVaDays($user, $currentYear),
            'pending'   => $this->leaveBalance->getPendingVaDays($user, $currentYear),
            'remaining' => $this->leaveBalance->getRemainingBalance($user, $currentYear),
            'year'      => $currentYear,
        ];

        return view('conges.index', compact('conges', 'historique', 'anneesDisponibles', 'selectedYear', 'balance'));
    }

    public function store(CongeRequest $congeRequest): RedirectResponse
    {
        $data = $congeRequest->validated();

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $halfDay = (bool) ($data['is_half_day'] ?? false);

        $nbJours = $this->leaveBalance->countWorkingDays(auth()->user(), $start, $end, $halfDay);

        if ($nbJours <= 0) {
            return back()
                ->withInput()
                ->withErrors(['start_date' => 'La période sélectionnée ne contient aucun jour ouvré.']);
        }

        $conge = new DemandeConge([
            'type'       => $data['type'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'nb_jours'   => $nbJours,
        ]);
        $conge->user_id = auth()->id();
        $conge->save();

        return to_route('mes-conges.index')->with('success', 'Votre demande de congé a bien été générée.');
    }

    public function generatePDF($id)
    {
        $conge = DemandeConge::with(['user', 'decidedBy'])->findOrFail($id);

        $conge->formattedStartDate = Carbon::parse($conge->start_date)->translatedFormat('d M Y');
        $conge->formattedEndDate = Carbon::parse($conge->end_date)->translatedFormat('d M Y');

        $conge->dateDu = [
            'jour' => Carbon::parse($conge->start_date)->format('d'),
            'mois' => Carbon::parse($conge->start_date)->format('m'),
            'annee' => Carbon::parse($conge->start_date)->format('Y'),
        ];

        $conge->dateAu = [
            'jour' => Carbon::parse($conge->end_date)->format('d'),
            'mois' => Carbon::parse($conge->end_date)->format('m'),
            'annee' => Carbon::parse($conge->end_date)->format('Y'),
        ];

        $pdf = PDF::loadView('pdf.conge', compact('conge'));

        return $pdf->stream('demande-conge-' . $conge->id . '.pdf');
    }

    public function update(CongeRequest $congeRequest, $id): RedirectResponse
    {
        $conge = DemandeConge::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'en_cours')
            ->firstOrFail();

        $data = $congeRequest->validated();

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $halfDay = (bool) ($data['is_half_day'] ?? false);

        $nbJours = $this->leaveBalance->countWorkingDays(auth()->user(), $start, $end, $halfDay);

        if ($nbJours <= 0) {
            return back()
                ->withInput()
                ->withErrors(['start_date' => 'La période sélectionnée ne contient aucun jour ouvré.']);
        }

        $conge->update([
            'type'       => $data['type'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'nb_jours'   => $nbJours,
        ]);

        return to_route('mes-conges.index')->with('success', 'Votre demande de congé a bien été modifiée.');
    }

    public function send($id): RedirectResponse
    {
        $conge = DemandeConge::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'en_cours')
            ->firstOrFail();

        $conge->update(['status' => 'envoyee']);

        return to_route('mes-conges.index')->with('success', 'Votre demande de congé a bien été envoyée.');
    }

    public function destroy($id)
    {
        $conge = DemandeConge::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $conge->delete();

        return to_route('mes-conges.index')->with('success', 'Votre demande de congé a bien été supprimée.');
    }
}
