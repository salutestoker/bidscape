<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStatusReminderRecipient extends Model
{
    protected $guarded = [];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(LeadStatusReminderRule::class, 'lead_status_reminder_rule_id');
    }
}
