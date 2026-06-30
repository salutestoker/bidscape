<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(int $companyId, ActivityEvent $event, string $description, ?Model $subject = null, ?int $userId = null, array $metadata = []): ActivityLog
    {
        return ActivityLog::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
