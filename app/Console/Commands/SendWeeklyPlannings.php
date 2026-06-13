<?php

namespace App\Console\Commands;

use App\Mail\PlanningEmail;
use App\Models\Agence;
use App\Models\User;
use App\Services\PlanningExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyPlannings extends Command
{
    protected $signature = 'planning:send-weekly
                            {--week= : Numéro de semaine ISO à envoyer}
                            {--year= : Année ISO à envoyer}
                            {--to=* : Adresses destinataires de remplacement (test)}';

    protected $description = 'Envoie le planning hebdomadaire de chaque agence à ses collaborateurs actifs et à la direction';

    public function handle(PlanningExportService $exporter): int
    {
        $target = now()->addWeek();
        $week = (int) ($this->option('week') ?: $target->isoWeek());
        $year = (int) ($this->option('year') ?: $target->isoWeekYear());

        $direction = User::direction()->get();
        $actingUser = $direction->first() ?? User::where('actif', true)->first();

        if (! $actingUser) {
            $this->warn('Aucun utilisateur actif disponible — envoi annulé.');

            return self::SUCCESS;
        }

        $override = $this->option('to');
        $agences = Agence::with('societe')->has('users')->get();
        $sent = 0;
        $failed = 0;

        foreach ($agences as $agence) {
            $recipients = ! empty($override)
                ? collect($override)
                : $agence->users()
                    ->where('users.actif', true)
                    ->get()
                    ->merge($direction)
                    ->unique('id');

            if ($recipients->isEmpty()) {
                continue;
            }

            $pdfPath   = $exporter->generatePdf($week, $year, $agence->id, $actingUser);
            $excelPath = $exporter->generateExcel($week, $year, $agence->id, $actingUser);

            try {
                Mail::to($recipients)->send(new PlanningEmail(
                    week: $week,
                    year: $year,
                    pdfPath: $pdfPath,
                    excelPath: $excelPath,
                    agenceName: $agence->display_name,
                ));

                $sent++;
                $this->info("✓ {$agence->display_name} : planning S{$week}-{$year} envoyé à {$recipients->count()} destinataire(s).");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("✗ {$agence->display_name} : échec d'envoi — {$e->getMessage()}");
                Log::warning('planning:send-weekly: échec envoi agence', [
                    'agence' => $agence->display_name,
                    'error'  => $e->getMessage(),
                ]);
            } finally {
                @unlink($pdfPath);
                @unlink($excelPath);
            }
        }

        $this->info("Terminé : {$sent} envoyé(s), {$failed} échec(s).");

        return self::SUCCESS;
    }
}
