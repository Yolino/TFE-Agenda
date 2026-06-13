<?php

namespace App\Services;

use App\Models\DemandeConge;
use App\Models\Planning;
use Illuminate\Support\Facades\DB;

class CongeCancellationService
{
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

        ActivityLogger::record('conge.cancelled', $conge, [
            'owner_user_id'      => $conge->user_id,
            'cancelled_by'       => $actorId,
            'on_behalf_of_other' => (int) $actorId !== (int) $conge->user_id,
            'type'               => $conge->type,
            'start_date'         => (string) $conge->start_date,
            'end_date'           => (string) $conge->end_date,
        ], 'Annulation de congé');
    }

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
