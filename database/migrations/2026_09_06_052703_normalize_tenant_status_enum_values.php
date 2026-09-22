<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tenants')
            ->whereNull('status')
            ->orWhere('status', 'active')
            ->update(['status' => 'Active']);

        DB::table('tenants')
            ->where('status', 'provisioning')
            ->update(['status' => 'Provisioning']);

        DB::table('tenants')
            ->where('status', 'suspended')
            ->update(['status' => 'Suspended']);

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('status')->default('Active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });
    }
};
