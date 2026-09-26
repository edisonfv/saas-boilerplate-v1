<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files attached to any central entity through a polymorphic relation
     * (App\Models\Concerns\HasAttachments), replacing the support-only
     * support_ticket_attachments table.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('attachable');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('central_users')->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        if (Schema::hasTable('support_ticket_attachments')) {
            DB::table('support_ticket_attachments')->orderBy('created_at')->each(function (object $row): void {
                DB::table('attachments')->insert([
                    'id' => $row->id,
                    'attachable_type' => $row->support_ticket_message_id ? 'support_ticket_message' : 'support_ticket',
                    'attachable_id' => $row->support_ticket_message_id ?? $row->support_ticket_id,
                    'disk' => $row->disk,
                    'path' => $row->path,
                    'original_name' => $row->original_name,
                    'mime_type' => $row->mime_type,
                    'size' => $row->size,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            });

            Schema::drop('support_ticket_attachments');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('support_ticket_message_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::dropIfExists('attachments');
    }
};
