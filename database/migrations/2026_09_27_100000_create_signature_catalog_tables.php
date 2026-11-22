<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central catalog of the electronic signatures the platform resells:
     * products (validity + container, with the unit price charged to
     * tenants on credit) and prepaid packages (N units of a product at a
     * special price).
     */
    public function up(): void
    {
        Schema::create('signature_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('validity');
            $table->string('container');
            $table->decimal('credit_unit_price', 10, 2);
            $table->decimal('suggested_retail_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['validity', 'container']);
        });

        Schema::create('signature_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_product_id')->constrained('signature_products')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_packages');
        Schema::dropIfExists('signature_products');
    }
};
