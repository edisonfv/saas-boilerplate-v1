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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('id');
            $table->string('legal_name')->nullable()->after('company_name');
            $table->string('tax_identifier', 50)->nullable()->after('legal_name');
            $table->char('country_code', 2)->nullable()->after('tax_identifier');
            $table->string('timezone')->default('UTC')->after('country_code');
            $table->string('primary_contact_name')->nullable()->after('timezone');
            $table->string('primary_contact_email')->nullable()->after('primary_contact_name');
            $table->string('status')->default('Active')->index()->after('primary_contact_email');
            $table->timestamp('provisioned_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn([
                'company_name',
                'legal_name',
                'tax_identifier',
                'country_code',
                'timezone',
                'primary_contact_name',
                'primary_contact_email',
                'status',
                'provisioned_at',
            ]);
        });
    }
};
