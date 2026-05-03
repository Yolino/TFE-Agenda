<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\PlanningRequest;
use App\Models\Planning;
use App\Models\PlanningTemplate;
use App\Models\User;
use App\Services\PlanningLockService;
use App\Services\CongeCancellationService;
use Carbon\Carbon;

class PlanningController extends Controller
{
    public function __construct(private CongeCancellationService $cancellationService)
    {
    }

    public function index(Request $request): View
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $startTime = '09:00';
        $endTime = '12:30';
        $startTimeAfternoon = '13:00';
        $endTimeAfternoon = '16:30';

        // Admin peut consulter le planning d'un autre utilisateur
        $targetUserId = auth()->id();
        $users = collect();

        if (auth()->user()->is_admin()) {
            $targetUserId = (int) $request->input('user_id', auth()->id());
            $users = User::where('actif', true)->orderBy('name')->get();
        }

        Gate::authorize('manage-planning', $targetUserId);

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

            $userEntries = Planning::where('user_id', $targetUserId)
                ->with('demandeConge')
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
                        'demande_conge_status' => $entry->demandeConge?->status,
                        'demande_conge_type' => $entry->demandeConge?->type,
                    ];
                });

        $firstEditableDate = PlanningLockService::firstEditableDate()->format('Y-m-d');

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
            'userEntries',
            'targetUserId',
            'users',
            'firstEditableDate'
        ));
    }

    public function store(PlanningRequest $planningRequest): JsonResponse
    {
        $credentials = $planningRequest->validated();

        Gate::authorize('manage-planning', (int) $credentials['user_id']);

        if (! PlanningLockService::isDateEditable($credentials['date'])) {
            return response()->json(['message' => 'Cette semaine est verrouillée. Vous ne pouvez modifier le planning qu\'à partir du lundi de la semaine suivante.'], 422);
        }

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
        $targetUserId = (int) $request->input('user_id', auth()->id());
        Gate::authorize('manage-planning', $targetUserId);

        $userTemplates = PlanningTemplate::where('user_id', $targetUserId)
            ->get()
            ->keyBy('day_of_week');
        $holidays = $request->input('holidays', []);

        Carbon::setLocale('fr');

        $startDate = new Carbon("{$year}-{$month}-01");
        $startDate->addWeeks($weekNumber - 1)->startOfWeek(Carbon::MONDAY);
        $endDate = clone $startDate;
        $endDate->endOfWeek(Carbon::SUNDAY);

        // Statuts protégés : l'auto ne les écrase jamais
        $protectedStatuses = ['maladie', 'conge', 'recup', 'css', 'indisponible', 'jour_ferie'];
        $protectedStatusIds = array_filter(
            array_map(fn($s) => Planning::STATUS_MAP[$s] ?? null, $protectedStatuses)
        );

        while ($startDate->lte($endDate)) {
            if (! PlanningLockService::isDateEditable($startDate)) {
                $startDate->addDay();
                continue;
            }

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

            // Ne pas écraser un jour déjà rempli avec un statut protégé
            $existing = Planning::where('user_id', $targetUserId)
                ->where('date', $startDate->format('Y-m-d'))
                ->first();

            if ($existing && in_array($existing->status_id, $protectedStatusIds)) {
                $startDate->addDay();
                continue;
            }

            Planning::updateOrCreate(
                [
                    'user_id' => $targetUserId,
                    'date'    => $startDate->format('Y-m-d'),
                ],
                [
                    'status_id'            => $template->status_id,
                    'start_time_morning'   => $template->start_time_morning,
                    'end_time_morning'     => $template->end_time_morning,
                    'start_time_afternoon' => $template->start_time_afternoon,
                    'end_time_afternoon'   => $template->end_time_afternoon,
                ]
            );

            $startDate->addDay();
        }

        return response()->json(['message' => 'Semaine remplie avec succès'], 200);
    }

    public function show(Request $request): JsonResponse
    {
        $targetUserId = (int) $request->input('user_id', auth()->id());
        Gate::authorize('manage-planning', $targetUserId);

        $entries = Planning::where('user_id', $targetUserId)
            ->with('demandeConge')
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
                    'demande_conge_status' => $entry->demandeConge?->status,
                    'demande_conge_type' => $entry->demandeConge?->type,
                ];
            });

        return response()->json(['entries' => $entries], 200);
    }

    public function update(PlanningRequest $planningRequest, $id): JsonResponse
    {
        $credentials = $planningRequest->validated();
        $entry = Planning::findOrFail($id);

        Gate::authorize('manage-planning', (int) $entry->user_id);

        if (! PlanningLockService::isDateEditable($entry->date) || ! PlanningLockService::isDateEditable($credentials['date'])) {
            return response()->json(['message' => 'Cette semaine est verrouillée. Vous ne pouvez modifier le planning qu\'à partir du lundi de la semaine suivante.'], 422);
        }

        // Si on modifie un jour adossé à une demande de congé : annulation cascade
        // de la demande + suppression de tous les jours liés (la tuile est ensuite
        // recréée selon les nouvelles données).
        if ($entry->demande_conge_id) {
            $newUserId = (int) $credentials['user_id'];
            $newDate = $credentials['date'];
            $newStatus = $credentials['status'] ?? null;

            // L'admin peut "réécrire" la tuile en gardant le même statut de congé :
            // dans ce cas, on ne casse pas la demande sous-jacente.
            $stillSameCongeContext = $entry->user_id === $newUserId
                && $entry->date === $newDate
                && in_array($newStatus, ['conge', 'recup', 'css'], true)
                && Planning::STATUS_MAP[$newStatus] === $entry->status_id;

            if (! $stillSameCongeContext) {
                $this->cancellationService->cancelFromPlanning($entry, auth()->id());

                Planning::create([
                    'user_id' => $newUserId,
                    'date' => $newDate,
                    'status_id' => Planning::STATUS_MAP[$newStatus] ?? null,
                    'start_time_morning' => $credentials['start_time'] ?? null,
                    'end_time_morning' => $credentials['end_time'] ?? null,
                    'start_time_afternoon' => $credentials['start_time_afternoon'] ?? null,
                    'end_time_afternoon' => $credentials['end_time_afternoon'] ?? null,
                ]);

                return response()->json([
                    'message' => 'Modification réussie. La demande de congé associée a été annulée.',
                    'conge_cancelled' => true,
                ], 200);
            }
        }

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

    public function destroy(Request $request, $id): JsonResponse
    {
        $entry = Planning::findOrFail($id);

        Gate::authorize('manage-planning', (int) $entry->user_id);

        if (! PlanningLockService::isDateEditable($entry->date)) {
            return response()->json(['message' => 'Cette semaine est verrouillée. Vous ne pouvez modifier le planning qu\'à partir du lundi de la semaine suivante.'], 422);
        }

        // Si la tuile supprimée est rattachée à une demande de congé,
        // on annule la demande complète : cela supprime également les autres
        // jours liés via le service.
        if ($entry->demande_conge_id) {
            $this->cancellationService->cancelFromPlanning($entry, auth()->id());

            return response()->json([
                'message' => 'Suppression réussie. La demande de congé associée a été annulée.',
                'conge_cancelled' => true,
            ], 200);
        }

        $entry->delete();
        return response()->json(['message' => 'Suppression réussie'], 200);
    }
}
