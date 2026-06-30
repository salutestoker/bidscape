<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('industry')->default('landscaping');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 32)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('logo_path')->nullable();
            $table->unsignedInteger('default_overhead_basis_points')->default(1000);
            $table->unsignedInteger('default_target_margin_basis_points')->default(3000);
            $table->string('currency', 3)->default('USD');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('title')->nullable()->after('avatar_path');
        });

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('site_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 32)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'name']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('site_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 32)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('status')->default('pending_contact');
            $table->string('contact_preference')->nullable();
            $table->text('project_interest')->nullable();
            $table->text('requested_project_specifications')->nullable();
            $table->text('site_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('gate_code')->nullable();
            $table->timestamp('site_visit_scheduled_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('physical_material');
            $table->string('sku')->nullable();
            $table->string('category');
            $table->string('unit', 40);
            $table->unsignedBigInteger('unit_cost_cents')->default(0);
            $table->unsignedInteger('markup_basis_points')->default(3000);
            $table->unsignedBigInteger('selling_price_cents')->default(0);
            $table->unsignedBigInteger('hourly_rate_cents')->nullable();
            $table->unsignedBigInteger('minimum_charge_cents')->nullable();
            $table->string('pricing_method')->nullable();
            $table->string('vendor')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_taxable')->default(true);
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->string('supplier_item_number')->nullable();
            $table->text('labor_burden_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'type']);
        });

        Schema::create('assemblies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->string('unit', 40);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('markup_basis_points')->default(3000);
            $table->unsignedInteger('overhead_basis_points')->default(1000);
            $table->unsignedInteger('target_margin_basis_points')->default(3000);
            $table->unsignedInteger('waste_factor_basis_points')->default(0);
            $table->decimal('base_depth_inches', 8, 2)->nullable();
            $table->decimal('labor_hours_per_unit', 10, 3)->default(0);
            $table->decimal('default_minutes_per_unit', 10, 3)->default(0);
            $table->decimal('production_rate_per_day', 12, 3)->nullable();
            $table->unsignedBigInteger('base_cost_cents')->default(0);
            $table->unsignedBigInteger('selling_price_cents')->default(0);
            $table->json('formula_metadata')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'category']);
        });

        Schema::create('assembly_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component_type')->default('material');
            $table->string('name');
            $table->string('unit', 40);
            $table->decimal('quantity_per_unit', 14, 6)->default(1);
            $table->string('quantity_formula')->nullable();
            $table->unsignedInteger('waste_basis_points')->default(0);
            $table->unsignedBigInteger('unit_cost_cents')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['assembly_id', 'sort_order']);
        });

        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('deposit_basis_points')->default(5000);
            $table->text('terms_text')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estimate_number');
            $table->string('project_name');
            $table->string('status')->default('draft');
            $table->string('builder_step')->default('scope');
            $table->unsignedInteger('overhead_basis_points')->default(1000);
            $table->unsignedInteger('target_margin_basis_points')->default(3000);
            $table->unsignedBigInteger('material_cost_cents')->default(0);
            $table->unsignedBigInteger('labor_cost_cents')->default(0);
            $table->unsignedBigInteger('equipment_cost_cents')->default(0);
            $table->unsignedBigInteger('delivery_cost_cents')->default(0);
            $table->unsignedBigInteger('direct_cost_cents')->default(0);
            $table->unsignedBigInteger('overhead_cents')->default(0);
            $table->unsignedBigInteger('profit_cents')->default(0);
            $table->unsignedBigInteger('selling_price_cents')->default(0);
            $table->unsignedInteger('gross_margin_basis_points')->default(0);
            $table->text('scope_summary')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('terms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('declined_reason')->nullable();
            $table->string('decline_reason_type')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('public_token_hash')->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();
            $table->string('signature_name')->nullable();
            $table->string('signature_email')->nullable();
            $table->string('signature_ip', 64)->nullable();
            $table->json('accepted_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'estimate_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assembly_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type')->default('assembly');
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 14, 3)->default(1);
            $table->string('unit', 40);
            $table->unsignedBigInteger('unit_price_cents')->default(0);
            $table->unsignedBigInteger('material_cost_cents')->default(0);
            $table->unsignedBigInteger('labor_cost_cents')->default(0);
            $table->unsignedBigInteger('equipment_cost_cents')->default(0);
            $table->unsignedBigInteger('delivery_cost_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedInteger('markup_basis_points')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('thumbnail_path')->nullable();
            $table->text('notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->timestamps();
            $table->index(['estimate_id', 'sort_order']);
            $table->index(['estimate_id', 'item_type']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number');
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->string('signature_name')->nullable();
            $table->string('signature_email')->nullable();
            $table->string('signed_document_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->json('accepted_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'contract_number']);
        });

        Schema::create('project_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('job_number');
            $table->string('project_name');
            $table->string('status')->default('sold');
            $table->unsignedBigInteger('contract_value_cents')->default(0);
            $table->string('deposit_status')->default('pending');
            $table->string('next_action')->nullable();
            $table->string('site_address')->nullable();
            $table->text('site_notes')->nullable();
            $table->timestamp('contract_signed_at')->nullable();
            $table->timestamp('packet_ready_at')->nullable();
            $table->timestamp('handed_off_at')->nullable();
            $table->json('accepted_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'job_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('job_packets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('packet_number');
            $table->string('status')->default('draft');
            $table->json('snapshot');
            $table->json('missing_requirements')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'packet_number']);
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('attachable');
            $table->string('original_filename');
            $table->string('display_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension', 16)->nullable();
            $table->string('type')->default('document');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('notable');
            $table->text('body');
            $table->string('visibility')->default('internal');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('subject');
            $table->string('event');
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('job_packets');
        Schema::dropIfExists('project_jobs');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('estimate_items');
        Schema::dropIfExists('estimates');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('assembly_components');
        Schema::dropIfExists('assemblies');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('lead_sources');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['avatar_path', 'title']);
        });

        Schema::dropIfExists('companies');
    }
};
