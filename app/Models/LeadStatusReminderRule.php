<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadStatusReminderRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'lead_status' => LeadStatus::class,
            'is_enabled' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(LeadStatusReminderRecipient::class)->orderBy('sort_order');
    }
}
