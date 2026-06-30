<?php

use App\Enums\LeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('pending_estimate_started_at')->nullable()->after('site_visit_scheduled_at');
            $table->timestamp('late_estimate_last_notified_at')->nullable()->after('pending_estimate_started_at');
        });

        DB::table('leads')
            ->where('status', LeadStatus::PendingEstimate->value)
            ->whereNull('pending_estimate_started_at')
            ->update([
                'pending_estimate_started_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn(['pending_estimate_started_at', 'late_estimate_last_notified_at']);
        });
    }
};
