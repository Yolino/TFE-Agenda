<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\JustificatifAbsence;
use Carbon\Carbon;

class JustificatifAbsenceController extends Controller
{
    public function index(Request $request): View
    {
        Carbon::setLocale('fr');

        $today = Carbon::today();

        $format = fn ($j) => tap($j, function ($item) {
            $item->formattedStartDate = Carbon::parse($item->start_date)->translatedFormat('d M Y');
            $item->formattedEndDate   = Carbon::parse($item->end_date)->translatedFormat('d M Y');
        });

        $justificatifs = JustificatifAbsence::where('user_id', auth()->id())
            ->where('end_date', '>=', $today)
            ->orderBy('start_date')
            ->get()
            ->map($format);

        $anneesDisponibles = JustificatifAbsence::where('user_id', auth()->id())
            ->where('end_date', '<', $today)
            ->selectRaw('YEAR(end_date) as annee')
            ->distinct()
            ->orderBy('annee', 'desc')
            ->pluck('annee');

        $selectedYear = $request->query('year', (string) $today->year);

        $historiqueQuery = JustificatifAbsence::where('user_id', auth()->id())
            ->where('end_date', '<', $today);

        if ($selectedYear !== 'all') {
            $historiqueQuery->whereYear('end_date', $selectedYear);
        }

        $historique = $historiqueQuery
            ->orderBy('start_date', 'desc')
            ->get()
            ->map($format);

        return view('conges.justificatif', compact('justificatifs', 'historique', 'anneesDisponibles', 'selectedYear'));
    }
}
