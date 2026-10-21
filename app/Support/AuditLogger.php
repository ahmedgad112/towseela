<?php

namespace App\Support;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(AuditAction $action, Model $entity, ?User $actor = null, array $metadata = []): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $actor?->id ?? auth()->id(),
            'action' => $action->value,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
        ]);
    }
}
