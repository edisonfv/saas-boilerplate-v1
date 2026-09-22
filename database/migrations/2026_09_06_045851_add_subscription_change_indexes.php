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
        Schema::table('subscription_changes', function (Blueprint $table) {
            $table->index(['status', 'effective_at']);
            $table->index(['subscription_id', 'status']);
        });

        Schema::table('subscription_modules', function (Blueprint $table) {
            $table->index(['subscription_id', 'source', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_modules', function (Blueprint $table) {
            $table->dropIndex(['subscription_id', 'source', 'ends_at']);
        });

        Schema::table('subscription_changes', function (Blueprint $table) {
            $table->dropIndex(['subscription_id', 'status']);
            $table->dropIndex(['status', 'effective_at']);
        });
    }
};
