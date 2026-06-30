<?php

namespace App\Models;

use Database\Factories\AssemblyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Assembly extends Model
{
    /** @use HasFactory<AssemblyFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'formula_metadata' => 'array',
            'base_depth_inches' => 'decimal:2',
            'labor_hours_per_unit' => 'decimal:3',
            'default_minutes_per_unit' => 'decimal:3',
            'production_rate_per_day' => 'decimal:3',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(AssemblyComponent::class)->orderBy('sort_order');
    }

    public function estimateItems(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
