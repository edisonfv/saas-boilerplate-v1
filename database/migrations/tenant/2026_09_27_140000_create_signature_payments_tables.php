<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * End-customer payments of signature requests (tenant database): a
     * request can only be sent to the provider once paid. Customers report
     * transfers/deposits with a receipt that staff confirms or rejects;
     * staff can also register a payment directly, or sell a prepaid
     * single-use invitation link before the application exists.
     */
    public function up(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            // Requests created before payments existed were already handled by staff.
            $table->string('payment_status')->default('Paid')->after('status');
            $table->index('payment_status');
        });

        Schema::create('signature_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('signature_product_id');
            $table->string('product_name');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->foreignUuid('signature_request_id')->nullable()->constrained('signature_requests')->nullOnDelete();
            $table->string('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });

        Schema::create('signature_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_request_id')->nullable()->constrained('signature_requests')->cascadeOnDelete();
            $table->foreignUuid('signature_invitation_id')->nullable()->constrained('signature_invitations')->nullOnDelete();
            $table->string('method');
            $table->decimal('amount', 10, 2);
            $table->string('reference')->nullable();
            $table->string('receipt_disk')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->string('review');
            $table->text('rejection_reason')->nullable();
            $table->string('reported_by_name')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->string('reviewed_by_name')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['signature_request_id', 'review']);
        });

        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->json('bank_accounts')->nullable()->after('faqs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->dropColumn('bank_accounts');
        });

        Schema::dropIfExists('signature_payments');
        Schema::dropIfExists('signature_invitations');

        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn('payment_status');
        });
    }
};
