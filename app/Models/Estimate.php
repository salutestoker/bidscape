<?php

namespace App\Models;

use App\Enums\EstimateDeclineReasonType;
use App\Enums\EstimateStatus;
use Database\Factories\EstimateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Estimate extends Model
{
    /** @use HasFactory<EstimateFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => EstimateStatus::class,
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
            'signed_at' => 'datetime',
            'declined_at' => 'datetime',
            'decline_reason_type' => EstimateDeclineReasonType::class,
            'expires_at' => 'datetime',
            'public_token_expires_at' => 'datetime',
            'accepted_snapshot' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            EstimateStatus::Draft->value,
            EstimateStatus::InReview->value,
            EstimateStatus::Emailed->value,
            EstimateStatus::SignaturePending->value,
            EstimateStatus::Approved->value,
        ]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class)->orderBy('sort_order');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
