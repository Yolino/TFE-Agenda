<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null
    ): ?ActivityLog {
        $user = Auth::user();

        try {
            return ActivityLog::create([
                'user_id'      => $user?->getKey(),
                'user_name'    => $user ? trim(($user->firstname ?? '') . ' ' . ($user->name ?? '')) : null,
                'action'       => $action,
                'description'  => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id'   => $subject?->getKey(),
                'properties'   => $properties ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('technique')->error('Échec écriture ActivityLog', [
                'action'  => $action,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function record(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null
    ): ?ActivityLog {
        return app(self::class)->log($action, $subject, $properties, $description);
    }
}
