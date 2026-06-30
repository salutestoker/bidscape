<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('estimates') && ! Schema::hasColumn('estimates', 'decline_reason_type')) {
            Schema::table('estimates', function (Blueprint $table): void {
                $table->string('decline_reason_type')->nullable()->after('declined_reason');
            });
        }

        if (! Schema::hasTable('leads')) {
            return;
        }

        $map = [
            'new' => 'pending_contact',
            'contacted' => 'pending_contact',
            'qualified' => 'pending_contact',
            'follow_up' => 'pending_contact',
            'site_visit_scheduled' => 'site_visit',
            'estimating' => 'pending_estimate',
            'estimate_emailed' => 'estimate_sent',
            'won' => 'approved',
            'lost' => 'closed',
        ];

        foreach ($map as $from => $to) {
            DB::table('leads')->where('status', $from)->update(['status' => $to]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('estimates') && Schema::hasColumn('estimates', 'decline_reason_type')) {
            Schema::table('estimates', function (Blueprint $table): void {
                $table->dropColumn('decline_reason_type');
            });
        }
    }
};
