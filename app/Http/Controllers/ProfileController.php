<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ProfileRequest;
use Illuminate\View\View;
use App\Models\Planning;
use App\Models\PlanningTemplate;
use App\Models\User;

class ProfileController extends Controller
{
    public function update(ProfileRequest $profileRequest): RedirectResponse
    {
        $user = User::find(auth()->user()->id);

        $validated = $profileRequest->validated();
        if ($validated['password'] === null) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }
        $user->update($validated);

        return to_route('profile.show')->with('success', 'Votre profile a été mis à jour.');
    }

    public function show(): View
    {
        $user = auth()->user();
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

            $planning->{$dayName} = trim(implode(',', array_filter([$morning, $afternoon])), ',');
            $planning->{$dayName . '_status'} = $template?->status ?? 'bureau';
        }

        return view('profile.index', ['planning' => $planning]);
    }

    public function updatePlanning(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $planningData = $request->input('planning');

        $dayMap = [
            'lundi' => 1,
            'mardi' => 2,
            'mercredi' => 3,
            'jeudi' => 4,
            'vendredi' => 5,
            'samedi' => 6,
            'dimanche' => 7,
        ];

        foreach ($planningData as $day => $data) {
            $status = $data['status'] ?? 'bureau';

            PlanningTemplate::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'day_of_week' => $dayMap[$day],
                ],
                [
                    'start_time_morning' => $data['morning_start'] ?? null,
                    'end_time_morning' => $data['morning_end'] ?? null,
                    'start_time_afternoon' => $data['afternoon_start'] ?? null,
                    'end_time_afternoon' => $data['afternoon_end'] ?? null,
                    'status_id' => Planning::STATUS_MAP[$status] ?? null,
                ]
            );
        }

        return to_route('profile.show')->with('success', 'Votre planning a été mis à jour.');
    }
}
