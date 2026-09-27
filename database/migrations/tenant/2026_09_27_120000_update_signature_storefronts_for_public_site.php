<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The storefront is now the tenant's always-public home page (no
     * publish switch), and the WhatsApp contact carries a configurable
     * pre-filled message.
     */
    public function up(): void
    {
        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->dropColumn('is_published');
            $table->string('whatsapp_message', 500)->nullable()->after('whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->dropColumn('whatsapp_message');
            $table->boolean('is_published')->default(false);
        });
    }
};
