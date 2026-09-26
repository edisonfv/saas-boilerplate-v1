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
        // Single-row table: global support parameters editable from Central.
        Schema::create('support_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->unsignedSmallInteger('booking_min_notice_hours')->default(2);
            $table->unsignedSmallInteger('booking_max_days_ahead')->default(14);
            $table->unsignedSmallInteger('cancellation_notice_hours')->default(2);
            $table->unsignedSmallInteger('auto_close_days')->default(5);
            $table->unsignedSmallInteger('reopen_window_days')->default(7);
            $table->unsignedSmallInteger('rating_link_days')->default(7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_settings');
    }
};
