<?php

namespace App\Services;

use Carbon\Carbon;

class PlanningLockService
{
    public static function firstEditableDate(?Carbon $reference = null): Carbon
    {
        $reference = ($reference ?? Carbon::now())->copy()->startOfDay();

        return $reference->copy()->next(Carbon::MONDAY)->startOfDay();
    }

    public static function isDateEditable(string|Carbon $date, ?Carbon $reference = null): bool
    {
        $date = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        return $date->gte(self::firstEditableDate($reference));
    }
}
