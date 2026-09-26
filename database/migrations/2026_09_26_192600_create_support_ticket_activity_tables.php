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
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('author_type');
            $table->foreignUuid('central_user_id')->nullable()->constrained('central_users')->nullOnDelete();
            $table->string('author_name');
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at']);
        });

        // Append-only audit trail (ISO 27001 traceability).
        Schema::create('support_ticket_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('actor_type');
            $table->foreignUuid('central_user_id')->nullable()->constrained('central_users')->nullOnDelete();
            $table->string('actor_name');
            $table->json('data')->nullable();
            $table->timestamp('created_at');

            $table->index(['support_ticket_id', 'created_at']);
        });

        Schema::create('support_time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('central_user_id')->constrained('central_users');
            $table->uuid('support_appointment_id')->nullable();
            $table->unsignedInteger('minutes');
            $table->string('description');
            $table->boolean('is_billable')->default(true);
            $table->date('worked_on');
            $table->timestamps();

            $table->index('worked_on');
        });

        Schema::create('support_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('central_user_id')->nullable()->constrained('central_users')->nullOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->boolean('was_resolved');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ratings');
        Schema::dropIfExists('support_time_entries');
        Schema::dropIfExists('support_ticket_events');
        Schema::dropIfExists('support_ticket_messages');
    }
};
