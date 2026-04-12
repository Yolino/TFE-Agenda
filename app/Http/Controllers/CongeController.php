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
    public function index(): View
    {
        $conges = DemandeConge::where('user_id', auth()->id())->get();

        // Définir la locale de Carbon en français
        Carbon::setLocale('fr');

        $congesFormatted = $conges->map(function ($conge) {
            $conge->formattedStartDate = Carbon::parse($conge->start_date)->translatedFormat('d M Y');
            $conge->formattedEndDate = Carbon::parse($conge->end_date)->translatedFormat('d M Y');
            return $conge;
        });

        return view('conges.index', [
            'conges' => $congesFormatted
        ]);
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
        $conge = DemandeConge::findOrFail($id);

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

    public function update(CongeRequest $congeRequest, $id): JsonResponse
    {
        // Valider les données de la requête
        $validatedData = DemandeConge::findOrFail($id);
        $validatedData->update($congeRequest->validated());

        return response()->json(['message' => 'Modification réussie'], 200);
    }

    public function destroy($id)
    {
        $conge = DemandeConge::findOrFail($id);
        $conge->delete();

        return to_route('mes-conges.index')->with('success', 'Votre demande de congé a bien été supprimée.');
    }
}
