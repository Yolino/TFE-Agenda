<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Requests\CongeRequest;
use App\Models\DemandeConge;
use Carbon\Carbon;
use PDF;

class CongeController extends Controller
{
    public function index(Request $request): View
    {
        Carbon::setLocale('fr');

        $today = Carbon::today();

        $format = fn ($conge) => tap($conge, function ($c) {
            $c->formattedStartDate = Carbon::parse($c->start_date)->translatedFormat('d M Y');
            $c->formattedEndDate   = Carbon::parse($c->end_date)->translatedFormat('d M Y');
        });

        $conges = DemandeConge::with('decidedBy')
            ->where('user_id', auth()->id())
            ->where('end_date', '>=', $today)
            ->orderBy('start_date')
            ->get()
            ->map($format);

        // Années disponibles pour le filtre historique
        $anneesDisponibles = DemandeConge::where('user_id', auth()->id())
            ->where('end_date', '<', $today)
            ->selectRaw('YEAR(end_date) as annee')
            ->distinct()
            ->orderBy('annee', 'desc')
            ->pluck('annee');

        $selectedYear = $request->query('year', (string) $today->year);

        $historiqueQuery = DemandeConge::with('decidedBy')
            ->where('user_id', auth()->id())
            ->where('end_date', '<', $today);

        if ($selectedYear !== 'all') {
            $historiqueQuery->whereYear('end_date', $selectedYear);
        }

        $historique = $historiqueQuery
            ->orderBy('start_date', 'desc')
            ->get()
            ->map($format);

        return view('conges.index', compact('conges', 'historique', 'anneesDisponibles', 'selectedYear'));
    }

    public function store(CongeRequest $congeRequest): RedirectResponse
    {
        $conge = new DemandeConge($congeRequest->validated());
        $conge->user_id = auth()->id();
        $conge->save();

        return to_route('mes-conges.index')->with('success', 'Votre demande de congé bien été générée.');
    }

    public function generatePDF($id)
    {
        $conge = DemandeConge::with('user')->findOrFail($id);

        // Formatage des dates
        $conge->formattedStartDate = Carbon::parse($conge->start_date)->translatedFormat('d M Y');
        $conge->formattedEndDate = Carbon::parse($conge->end_date)->translatedFormat('d M Y');

        $conge->dateDu = [
            'jour' => Carbon::parse($conge->start_date)->format('d'), // Jour sur 2 chiffres
            'mois' => Carbon::parse($conge->start_date)->format('m'), // Mois sur 2 chiffres
            'annee' => Carbon::parse($conge->start_date)->format('Y'), // Année sur 4 chiffres
        ];

        $conge->dateAu = [
            'jour' => Carbon::parse($conge->end_date)->format('d'), // Jour sur 2 chiffres
            'mois' => Carbon::parse($conge->end_date)->format('m'), // Mois sur 2 chiffres
            'annee' => Carbon::parse($conge->end_date)->format('Y'), // Année sur 4 chiffres
        ];

        $pdf = PDF::loadView('pdf.conge', compact('conge'));

        // Retourner le PDF comme réponse HTTP pour l'afficher dans le navigateur
        return $pdf->stream('demande-conge-' . $conge->id . '.pdf');
    }

    public function update(CongeRequest $congeRequest, $id): RedirectResponse
    {
        $conge = DemandeConge::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'en_cours')
            ->firstOrFail();

        $conge->update($congeRequest->validated());

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
