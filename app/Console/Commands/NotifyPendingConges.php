<?php

namespace App\Console\Commands;

use App\Mail\PendingCongesAlert;
use App\Models\DemandeConge;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyPendingConges extends Command
{
    protected $signature = 'conges:notify-pending
                            {--to=* : Adresses destinataires de remplacement (test)}';

    protected $description = 'Alerte la direction des demandes de congé en attente de traitement';

    public function handle(): int
    {
        $demandes = DemandeConge::with('user.agences.societe')
            ->where('status', 'envoyee')
            ->orderBy('created_at')
            ->get();

        if ($demandes->isEmpty()) {
            $this->info('✓ Aucune demande de congé en attente — aucun email envoyé.');

            return self::SUCCESS;
        }

        $recipients = ! empty($this->option('to'))
            ? collect($this->option('to'))
            : User::direction()->get();

        if ($recipients->isEmpty()) {
            $this->warn('Demandes en attente mais aucun destinataire « direction » — envoi annulé.');

            return self::SUCCESS;
        }

        $demandesByAgence = $demandes->groupBy(
            fn ($conge) => $conge->user?->agences->first()?->display_name ?? 'Sans agence'
        );

        Mail::to($recipients)->send(new PendingCongesAlert(
            count: $demandes->count(),
            demandesByAgence: $demandesByAgence,
        ));

        $this->info("✓ Alerte envoyée : {$demandes->count()} demande(s) → {$recipients->count()} destinataire(s).");

        return self::SUCCESS;
    }
}
