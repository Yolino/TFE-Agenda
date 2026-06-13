<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCronEmails extends Command
{
    protected $signature = 'crons:test
                            {--to=* : Adresses destinataires (défaut : tc@bti-belgium.be)}';

    protected $description = 'Déclenche immédiatement les CRON 1 (plannings) et CRON 2 (congés) vers des adresses de test';

    private const DEFAULT_RECIPIENTS = ['tc@bti-belgium.be'];

    public function handle(): int
    {
        $recipients = $this->option('to') ?: self::DEFAULT_RECIPIENTS;

        $this->info('Destinataires de test : ' . implode(', ', $recipients));
        $this->newLine();

        $this->info('── CRON 1 : plannings hebdomadaires ──');
        $this->call('planning:send-weekly', ['--to' => $recipients]);

        $this->newLine();
        $this->info('── CRON 2 : congés en attente ──');
        $this->call('conges:notify-pending', ['--to' => $recipients]);

        $this->newLine();
        $this->info('✓ Test terminé.');

        return self::SUCCESS;
    }
}
