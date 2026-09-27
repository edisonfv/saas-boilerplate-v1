<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photos of the public website's main banner (a slider) and its search
     * snippet (title and meta description).
     */
    public function up(): void
    {
        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->json('hero_slides')->nullable()->after('description');
            $table->string('seo_title', 70)->nullable()->after('hero_slides');
            $table->string('seo_description', 160)->nullable()->after('seo_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_storefronts', function (Blueprint $table) {
            $table->dropColumn(['hero_slides', 'seo_title', 'seo_description']);
        });
    }
};
