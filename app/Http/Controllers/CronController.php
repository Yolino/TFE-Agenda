<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PlanningEmail;
use Carbon\Carbon;

class CronController extends Controller
{
    public function sendPlanning()
    {
        $timezone = 'Europe/Brussels';
        $nextMonday = Carbon::now($timezone)->next(Carbon::MONDAY);
        $nextSaturday = $nextMonday->copy()->addDays(5);

        $dateRange = $nextMonday->format('d/m/Y') . ' au ' . $nextSaturday->format('d/m/Y');
        $fromAddress = env('MAIL_FROM_ADDRESS');
        $fromName = 'Luca Guglielmi';
        $subject = 'Planning du ' . $dateRange;
        $originalPath = public_path('pdf/planning_luca.pdf');
        $newFileName = 'luca_guglielmi_planning_du_' . str_replace([" ", "/"], "_", $dateRange) . '.pdf';
        $newPath = public_path('pdf/' . $newFileName);

        // Copy the file to a new location
        if (!copy($originalPath, $newPath)) {
            Log::error('Failed to copy file (' . $newFileName . ').');
            // return response()->json(['message' => 'Failed to copy file.'], 500);
        }

        Mail::to('salaire@pilote.be')->send(new PlanningEmail($fromAddress, $fromName, $subject, $newPath, $dateRange));

        // Delete the file
        unlink($newPath);

        Log::info('Email sent successfully (' . $newFileName . ').');
        // return response()->json(['message' => 'Email sent.'], 200);
    }
}
