<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'brand_background_color')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('brand_background_color');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'brand_background_color')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('brand_background_color', 16)->default('#f7f8f5')->after('brand_primary_color');
        });
    }
};
