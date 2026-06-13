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

    public function workingDatesBetween(User $user, Carbon $start, Carbon $end): array
    {
        $worksSaturday = $this->worksOnSaturday($user);

        $dates = [];
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            $dow = $date->dayOfWeekIso;

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

    public function getUsedVaDays(User $user, int $year): float
    {
        return (float) DemandeConge::where('user_id', $user->id)
            ->where('type', 'conge')
            ->where('status', 'acceptee')
            ->whereYear('start_date', $year)
            ->sum('nb_jours');
    }

    public function getPendingVaDays(User $user, int $year): float
    {
        return (float) DemandeConge::where('user_id', $user->id)
            ->where('type', 'conge')
            ->where('status', 'envoyee')
            ->whereYear('start_date', $year)
            ->sum('nb_jours');
    }

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
