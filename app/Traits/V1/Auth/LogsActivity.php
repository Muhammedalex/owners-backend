<?php

namespace App\Traits\V1\Auth;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Log user activity.
     */
    protected function logActivity(string $action, array $context = []): void
    {
        Log::info("User Activity: {$action}", [
            'user_id' => auth()->id() ?? null,
            'action' => $action,
            'context' => $context,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

