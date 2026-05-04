<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Planning;
use App\Models\PlanningTemplate;
use App\Models\User;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();

        if ($user->planningTemplates()->count() === 0) {
            $this->createDefaultTemplates($user);
        }

        $templates = $user->planningTemplates->keyBy('day_of_week');
        $days = [
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            7 => 'dimanche',
        ];

        $planning = (object) [];
        foreach ($days as $dayNumber => $dayName) {
            $template = $templates->get($dayNumber);

            $morning = ($template?->start_time_morning && $template?->end_time_morning)
                ? $template->start_time_morning . '-' . $template->end_time_morning
                : null;

            $afternoon = ($template?->start_time_afternoon && $template?->end_time_afternoon)
                ? $template->start_time_afternoon . '-' . $template->end_time_afternoon
                : null;

            $planning->{$dayName}            = trim(implode(',', array_filter([$morning, $afternoon])), ',');
            $planning->{$dayName . '_status'} = $template?->status ?? 'bureau';
        }

        return view('profile.index', ['planning' => $planning]);
    }

    private function createDefaultTemplates(User $user): void
    {
        $defaults = [
            ['day' => 1, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 2, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 3, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 4, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 5, 'status' => 'bureau', 'ms' => '09:00', 'me' => '12:30', 'as' => '13:00', 'ae' => '16:30'],
            ['day' => 6, 'status' => 'recup',  'ms' => null,    'me' => null,    'as' => null,    'ae' => null],
            ['day' => 7, 'status' => 'conge',  'ms' => null,    'me' => null,    'as' => null,    'ae' => null],
        ];

        foreach ($defaults as $t) {
            PlanningTemplate::create([
                'user_id'              => $user->id,
                'day_of_week'          => $t['day'],
                'start_time_morning'   => $t['ms'],
                'end_time_morning'     => $t['me'],
                'start_time_afternoon' => $t['as'],
                'end_time_afternoon'   => $t['ae'],
                'status_id'            => Planning::STATUS_MAP[$t['status']],
            ]);
        }
    }

    public function updatePlanning(Request $request): RedirectResponse
    {
        $user        = auth()->user();
        $planningData = $request->input('planning');

        $dayMap = [
            'lundi'    => 1,
            'mardi'    => 2,
            'mercredi' => 3,
            'jeudi'    => 4,
            'vendredi' => 5,
            'samedi'   => 6,
            'dimanche' => 7,
        ];

        foreach ($planningData as $day => $data) {
            $status = $data['status'] ?? 'bureau';

            PlanningTemplate::updateOrCreate(
                [
                    'user_id'      => $user->id,
                    'day_of_week'  => $dayMap[$day],
                ],
                [
                    'start_time_morning'   => $data['morning_start'] ?? null,
                    'end_time_morning'     => $data['morning_end'] ?? null,
                    'start_time_afternoon' => $data['afternoon_start'] ?? null,
                    'end_time_afternoon'   => $data['afternoon_end'] ?? null,
                    'status_id'            => Planning::STATUS_MAP[$status] ?? null,
                ]
            );
        }

        return to_route('profile.show')->with('success', 'Votre horaire prédéfini a été mis à jour.');
    }
}
