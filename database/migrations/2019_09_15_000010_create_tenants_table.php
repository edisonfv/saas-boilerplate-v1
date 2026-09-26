<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('tax_identifier', 50)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_email')->nullable();
            $table->string('status')->default(TenantStatus::Active()->value)->index();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
