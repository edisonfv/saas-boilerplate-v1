<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('number')->unique();
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->string('channel');

            // Tenant users live in each tenant's own database, so the
            // requester is a snapshot (plus the tenant-side id), not a FK.
            $table->string('requester_tenant_user_id')->nullable();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_company')->nullable();
            $table->text('access_token')->nullable();

            $table->string('subject');
            $table->text('description');
            $table->string('category');
            $table->string('priority');
            $table->string('status');
            $table->foreignUuid('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('central_users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('central_users')->nullOnDelete();

            // Whether the tenant had the Support module when the ticket was
            // opened: decides the default billing mode and whether SLA applies.
            $table->boolean('covered_by_plan')->default(false);
            $table->string('billing_mode');
            $table->string('billing_status');
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->text('billing_reason')->nullable();
            $table->string('invoice_reference')->nullable();

            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('central_users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['billing_status', 'billing_mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
