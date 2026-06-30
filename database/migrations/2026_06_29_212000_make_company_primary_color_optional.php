<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')
            ->where('brand_primary_color', '#07883f')
            ->update(['brand_primary_color' => null]);

        DB::statement('ALTER TABLE companies MODIFY brand_primary_color VARCHAR(16) NULL');
    }

    public function down(): void
    {
        DB::table('companies')
            ->whereNull('brand_primary_color')
            ->update(['brand_primary_color' => '#07883f']);

        DB::statement("ALTER TABLE companies MODIFY brand_primary_color VARCHAR(16) NOT NULL DEFAULT '#07883f'");
    }
};
