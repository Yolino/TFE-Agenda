<?php

namespace App\Services;

use App\Models\DemandeConge;
use App\Models\PlanningTemplate;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveBalanceService
{
    public const BASE_ANNUAL_VA = 20;

    /**
     * Renvoie les jours ouvrés effectifs pour un utilisateur entre deux dates.
     * Règles :
     *  - Dimanche : toujours exclu.
     *  - Samedi : exclu sauf si le planning_template de l'utilisateur indique
     *    bureau / tele_travail pour ce jour (day_of_week = 6).
     *  - Autres jours : inclus par défaut.
     */
    public function workingDatesBetween(User $user, Carbon $start, Carbon $end): array
    {
        $worksSaturday = $this->worksOnSaturday($user);

        $dates = [];
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            $dow = $date->dayOfWeekIso; // 1 = lundi ... 7 = dimanche

            if ($dow === 7) {
                continue;
            }

            if ($dow === 6 && !$worksSaturday) {
                continue;
            }

            $dates[] = $date->copy();
        }

        return $dates;
    }

    /**
     * Calcule le nombre de jours à décompter pour une demande.
     * Une demi-journée compte 0.5 (et n'a de sens que sur 1 jour ouvré).
     */
    public function countWorkingDays(User $user, Carbon $start, Carbon $end, bool $halfDay = false): float
    {
        $dates = $this->workingDatesBetween($user, $start, $end);
        $count = count($dates);

        if ($count === 0) {
            return 0.0;
        }

        if ($halfDay && $count === 1) {
            return 0.5;
        }

        return (float) $count;
    }

    /**
     * Total de jours de Vacances Annuelles déjà consommés (status acceptee)
     * sur une année donnée, en se basant sur la date de début.
     */
    public function getUsedVaDays(User $user, int $year): float
    {
        return (float) DemandeConge::where('user_id', $user->id)
            ->where('type', 'conge')
            ->where('status', 'acceptee')
            ->whereYear('start_date', $year)
            ->sum('nb_jours');
    }

    /**
     * Total de jours réservés (status envoyee) — utile pour afficher un solde projeté.
     */
    public function getPendingVaDays(User $user, int $year): float
    {
        return (float) DemandeConge::where('user_id', $user->id)
            ->where('type', 'conge')
            ->where('status', 'envoyee')
            ->whereYear('start_date', $year)
            ->sum('nb_jours');
    }

    /**
     * Solde restant de VA pour l'année (base 20 jours).
     */
    public function getRemainingBalance(User $user, int $year, float $base = self::BASE_ANNUAL_VA): float
    {
        return $base - $this->getUsedVaDays($user, $year);
    }

    private function worksOnSaturday(User $user): bool
    {
        $saturday = PlanningTemplate::where('user_id', $user->id)
            ->where('day_of_week', 6)
            ->first();

        if (!$saturday) {
            return false;
        }

        return in_array($saturday->status, ['bureau', 'tele_travail'], true);
    }
}
