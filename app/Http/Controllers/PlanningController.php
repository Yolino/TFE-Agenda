<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Requests\PlanningRequest;
use App\Models\Planning;
use App\Models\PlanningTemplate;
use Carbon\Carbon;

class PlanningController extends Controller
{
    public function index(): View
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $startTime = '09:00';
        $endTime = '12:30';
        $startTimeAfternoon = '13:00';
        $endTimeAfternoon = '16:30';

        // Génération des options de mois
        $months = collect(range(1, 12))->mapWithKeys(function ($month) {
            return [$month => Carbon::create(null, $month)->locale('fr')->isoFormat('MMMM')];
        });

        // Génération des options d'année (uniquement l'année courante et la suivante)
        $years = collect([$currentYear, $currentYear + 1]);

        // Affichage des jours de la semaine
        $weekDays = collect(range(0, 6))->map(function ($day) {
            return Carbon::now()->startOfWeek()->addDays($day)->locale('fr')->isoFormat('dddd');
        });

            $userEntries = Planning::where('user_id', auth()->id())
                ->get()
                ->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'user_id' => $entry->user_id,
                        'date' => $entry->date,
                        'status' => $entry->status,
                        'start_time' => $entry->start_time ? $entry->start_time : null,
                        'end_time' => $entry->end_time ? $entry->end_time : null,
                        'start_time_afternoon' => $entry->start_time_afternoon ? $entry->start_time_afternoon : null,
                        'end_time_afternoon' => $entry->end_time_afternoon ? $entry->end_time_afternoon : null,
                    ];
                });

        return view('planning.index', compact(
            'months',
            'years',
            'weekDays',
            'currentYear',
            'currentMonth',
            'startTime',
            'endTime',
            'startTimeAfternoon',
            'endTimeAfternoon',
            'userEntries'
        ));
    }

    public function store(PlanningRequest $planningRequest): JsonResponse
    {
        $credentials = $planningRequest->validated();

        Planning::create([
            'user_id' => $credentials['user_id'],
            'date' => $credentials['date'],
            'status_id' => Planning::STATUS_MAP[$credentials['status']] ?? null,
            'start_time_morning' => $credentials['start_time'] ?? null,
            'end_time_morning' => $credentials['end_time'] ?? null,
            'start_time_afternoon' => $credentials['start_time_afternoon'] ?? null,
            'end_time_afternoon' => $credentials['end_time_afternoon'] ?? null,
        ]);

        return response()->json(['message' => 'Succès'], 200);
    }

    public function fillWeek(Request $request, $year, $month, $weekNumber): JsonResponse
    {
        $userTemplates = PlanningTemplate::where('user_id', auth()->id())
            ->get()
            ->keyBy('day_of_week');
        $holidays = $request->input('holidays', []); // Récupère les jours fériés du corps de la requête

        // Définir la locale de Carbon en français
        Carbon::setLocale('fr');

        $startDate = new Carbon("{$year}-{$month}-01");
        $startDate->addWeeks($weekNumber - 1)->startOfWeek(Carbon::MONDAY);
        $endDate = clone $startDate;
        $endDate->endOfWeek(Carbon::SUNDAY);

        while ($startDate->lte($endDate)) {
            if (in_array($startDate->format('Y-m-d'), $holidays)) {
                $startDate->addDay();
                continue;
            }

            $template = $userTemplates->get((int) $startDate->isoWeekday());

            if (!$template) {
                $startDate->addDay();
                continue;
            }

            $status = $template->status;
            if ($status === 'neant') {
                $startDate->addDay();
                continue;
            }

            Planning::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'date' => $startDate->format('Y-m-d')
                ],
                [
                    'status_id' => $template->status_id,
                    'start_time_morning' => $template->start_time_morning,
                    'end_time_morning' => $template->end_time_morning,
                    'start_time_afternoon' => $template->start_time_afternoon,
                    'end_time_afternoon' => $template->end_time_afternoon,
                ]
            );

            $startDate->addDay();
        }

        return response()->json(['message' => 'Semaine remplie avec succès'], 200);
    }

    public function show(): JsonResponse
    {
        $userId = auth()->id(); // Récupère l'ID de l'utilisateur connecté

        // Récupère les entrées de planning pour cet utilisateur
        $entries = Planning::where('user_id', $userId)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'date' => $entry->date,
                    'status' => $entry->status,
                    'start_time' => $entry->start_time ? $entry->start_time : null,
                    'end_time' => $entry->end_time ? $entry->end_time : null,
                    'start_time_afternoon' => $entry->start_time_afternoon ? $entry->start_time_afternoon : null,
                    'end_time_afternoon' => $entry->end_time_afternoon ? $entry->end_time_afternoon : null,
                ];
            });

        return response()->json(['entries' => $entries], 200);
    }

    public function update(PlanningRequest $planningRequest, $id): JsonResponse
    {
        $credentials = $planningRequest->validated();
        $entry = Planning::findOrFail($id);
        $entry->update([
            'user_id' => $credentials['user_id'],
            'date' => $credentials['date'],
            'status_id' => Planning::STATUS_MAP[$credentials['status']] ?? null,
            'start_time_morning' => $credentials['start_time'] ?? null,
            'end_time_morning' => $credentials['end_time'] ?? null,
            'start_time_afternoon' => $credentials['start_time_afternoon'] ?? null,
            'end_time_afternoon' => $credentials['end_time_afternoon'] ?? null,
        ]);

        return response()->json(['message' => 'Modification réussie'], 200);
    }

    public function destroy($id): JsonResponse
    {
        $userId = auth()->id();
        $entry = Planning::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();
        $entry->delete();
        return response()->json(['message' => 'Suppression réussie'], 200);
    }
}
