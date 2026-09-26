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
        Schema::create('support_attendance_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Team-wide opening hours (ISO weekday 1 = Monday ... 7 = Sunday).
        Schema::create('support_business_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedTinyInteger('weekday');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();

            $table->index('weekday');
        });

        Schema::create('support_technicians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('central_user_id')->unique()->constrained('central_users')->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('support_technician_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_technician_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->index(['support_technician_id', 'weekday']);
        });

        // Holidays (technician null = whole team) and technician absences.
        Schema::create('support_blackouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_technician_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason');
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_blackouts');
        Schema::dropIfExists('support_technician_shifts');
        Schema::dropIfExists('support_technicians');
        Schema::dropIfExists('support_business_hours');
        Schema::dropIfExists('support_attendance_types');
    }
};
