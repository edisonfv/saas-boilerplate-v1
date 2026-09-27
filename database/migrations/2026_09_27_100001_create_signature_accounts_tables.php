<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tenant's commercial standing as a signature distributor. The
     * ledger is the append-only source of truth; `credit_used` and
     * `signature_balances.available_units` are running totals kept in the
     * same transaction (under a row lock) so quota checks are O(1) and safe
     * against concurrent sales.
     */
    public function up(): void
    {
        Schema::create('signature_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->unique();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('affiliation_mode');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('credit_used', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('signature_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_account_id')->constrained('signature_accounts')->cascadeOnDelete();
            $table->foreignUuid('signature_product_id')->constrained('signature_products')->cascadeOnDelete();
            $table->integer('available_units')->default(0);
            $table->timestamps();

            $table->unique(['signature_account_id', 'signature_product_id']);
        });

        Schema::create('signature_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_account_id')->constrained('signature_accounts')->cascadeOnDelete();
            $table->foreignUuid('signature_product_id')->nullable()->constrained('signature_products')->nullOnDelete();
            $table->foreignUuid('signature_package_id')->nullable()->constrained('signature_packages')->nullOnDelete();
            $table->string('type');
            $table->integer('units')->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('reverses_entry_id')->nullable()->constrained('signature_ledger_entries')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('central_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['signature_account_id', 'created_at']);
            $table->index(['signature_account_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_ledger_entries');
        Schema::dropIfExists('signature_balances');
        Schema::dropIfExists('signature_accounts');
    }
};
