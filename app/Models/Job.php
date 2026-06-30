<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Job extends Model
{
    protected $table = 'project_jobs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'deposit_status' => DepositStatus::class,
            'contract_signed_at' => 'datetime',
            'packet_ready_at' => 'datetime',
            'handed_off_at' => 'datetime',
            'accepted_snapshot' => 'array',
        ];
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

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function packet(): HasOne
    {
        return $this->hasOne(JobPacket::class, 'project_job_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'project_job_id');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
