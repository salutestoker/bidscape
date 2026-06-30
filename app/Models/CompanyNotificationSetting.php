<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyNotificationSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'include_pipeline_summary' => 'boolean',
            'include_late_estimates' => 'boolean',
            'include_recent_activity' => 'boolean',
            'include_sales_summary' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
