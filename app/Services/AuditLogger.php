<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Record a critical change with actor, time, action and an optional reason.
     *
     * @param  array<string, mixed>  $changes
     */
    public function log(
        ?User $actor,
        string $action,
        ?Model $subject = null,
        ?string $reason = null,
        array $changes = [],
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'reason' => $reason,
            'changes' => $changes ?: null,
        ]);
    }
}
