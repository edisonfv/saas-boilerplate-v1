<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central side of the provider integration. The applicant's personal
     * data and documents stay in the tenant's own database; central only
     * keeps what it needs to route provider webhooks back to the right
     * tenant and to report sales: the provider token, the tenant-side
     * request id, the consumed ledger entry and the last known status.
     */
    public function up(): void
    {
        Schema::create('signature_provider_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_account_id')->constrained('signature_accounts')->cascadeOnDelete();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('tenant_request_id');
            $table->foreignUuid('signature_product_id')->nullable()->constrained('signature_products')->nullOnDelete();
            $table->foreignUuid('consumption_entry_id')->nullable()->constrained('signature_ledger_entries')->nullOnDelete();
            $table->string('provider');
            $table->string('provider_token')->nullable()->unique();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'tenant_request_id']);
            $table->index(['signature_account_id', 'status']);
        });

        Schema::create('signature_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('fingerprint')->unique();
            $table->string('event_type')->nullable();
            $table->string('provider_token')->nullable()->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_webhook_events');
        Schema::dropIfExists('signature_provider_requests');
    }
};
