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

        DB::transaction(function () use ($conge, $cancelledByUserId) {
            Planning::where('demande_conge_id', $conge->id)->delete();

            $conge->update([
                'status'       => 'annulee',
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledByUserId ?? auth()->id(),
            ]);
        });
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
