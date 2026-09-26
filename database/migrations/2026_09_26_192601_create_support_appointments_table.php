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
        Schema::create('support_appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('support_attendance_type_id')->constrained();
            $table->foreignUuid('support_technician_id')->constrained();
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status');
            $table->string('meeting_url')->nullable();
            $table->string('booked_by_name');
            $table->string('booked_by_email');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['support_technician_id', 'starts_at', 'ends_at'], 'support_appointments_technician_time_index');
            $table->index(['status', 'starts_at'], 'support_appointments_status_time_index');
            $table->index(['tenant_id', 'status'], 'support_appointments_tenant_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_appointments');
    }
};
