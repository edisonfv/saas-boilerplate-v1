<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable content of the public website's sections. NULL means "use
     * the default copy"; an empty list hides the section.
     */
    public function up(): void
    {
        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->json('uses')->nullable()->after('whatsapp_message');
            $table->json('steps')->nullable()->after('uses');
            $table->json('faqs')->nullable()->after('steps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->dropColumn(['uses', 'steps', 'faqs']);
        });
    }
};
