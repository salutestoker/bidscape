<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssemblyComponent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity_per_unit' => 'decimal:6',
            'is_optional' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
