<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The limits a subscription contracted, frozen from its plan at
     * subscribe/plan-change time.
     */
    public function up(): void
    {
        Schema::create('subscription_limit', function (Blueprint $table) {
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('limit_type_id')->constrained()->restrictOnDelete();
            $table->integer('value');

            $table->primary(['subscription_id', 'limit_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_limit');
    }
};
