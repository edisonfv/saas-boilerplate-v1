<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The features a subscription contracted, frozen from its plan at
     * subscribe/plan-change time.
     */
    public function up(): void
    {
        Schema::create('subscription_feature', function (Blueprint $table) {
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('feature_id')->constrained()->restrictOnDelete();

            $table->primary(['subscription_id', 'feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_feature');
    }
};
