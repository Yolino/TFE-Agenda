<?php

namespace App\Services;

use App\Models\DemandeConge;
use App\Models\Planning;
use Illuminate\Support\Facades\DB;

/**
 * Centralise l'annulation d'une demande de congé et la suppression
 * en cascade des jours associés sur le planning.
 */
class CongeCancellationService
{
    /**
     * Annule la demande et nettoie le planning lié.
     * Idempotent : si la demande est déjà annulée, ne fait rien.
     */
    public function cancel(DemandeConge $conge, ?int $cancelledByUserId = null): void
    {
        if ($conge->status === 'annulee') {
            return;
        }

        $actorId = $cancelledByUserId ?? auth()->id();

        DB::transaction(function () use ($conge, $actorId) {
            Planning::where('demande_conge_id', $conge->id)->delete();

            $conge->update([
                'status'       => 'annulee',
                'cancelled_at' => now(),
                'cancelled_by' => $actorId,
            ]);
        });

        // LOG MÉTIER : point unique pour TOUTES les annulations (directe OU via le
        // planning par un admin). "on_behalf_of_other" signale qu'un tiers a annulé
        // le congé d'un autre utilisateur (ActivityLogger est dans le même namespace).
        ActivityLogger::record('conge.cancelled', $conge, [
            'owner_user_id'      => $conge->user_id,
            'cancelled_by'       => $actorId,
            'on_behalf_of_other' => (int) $actorId !== (int) $conge->user_id,
            'type'               => $conge->type,
            'start_date'         => (string) $conge->start_date,
            'end_date'           => (string) $conge->end_date,
        ], 'Annulation de congé');
    }

    /**
     * Annule la demande de congé liée à une entrée Planning donnée
     * (utilisé quand l'admin supprime/modifie un jour de congé sur le planning).
     * Retourne la demande annulée, ou null si aucune demande liée.
     */
    public function cancelFromPlanning(Planning $planning, ?int $cancelledByUserId = null): ?DemandeConge
    {
        if (! $planning->demande_conge_id) {
            return null;
        }

        $conge = DemandeConge::find($planning->demande_conge_id);
        if (! $conge || $conge->status === 'annulee') {
            return $conge;
        }

        $this->cancel($conge, $cancelledByUserId);
        return $conge->fresh();
    }
}
