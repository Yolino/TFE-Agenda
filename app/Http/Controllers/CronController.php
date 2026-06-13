<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    public function planningHebdo(): Response
    {
        return $this->run('planning:send-weekly');
    }

    public function congesAttente(): Response
    {
        return $this->run('conges:notify-pending');
    }

    public function test(): Response
    {
        @set_time_limit(0);
        Artisan::call('crons:test');

        return response(Artisan::output(), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function run(string $command): Response
    {
        if (! config('crons.emails_enabled')) {
            return response("CRON désactivé (EMAIL_CRONS_ENABLED=false) — {$command} non exécuté.\n", 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        @set_time_limit(0);
        Artisan::call($command);

        return response(Artisan::output(), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
