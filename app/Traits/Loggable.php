<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;

trait Loggable
{
    public function logActivity(
        string $action,
        array $properties = [],
        ?string $description = null
    ): ?ActivityLog {
        return ActivityLogger::record($action, $this, $properties, $description);
    }
}
