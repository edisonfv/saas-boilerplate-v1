<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operational side of the Signatures module, in the tenant's own
     * database: the applications the tenant sells to its customers (with
     * their personal data and KYC documents), their status history, and
     * the tenant's public storefront settings. The product is a snapshot
     * of the central catalog at capture time (it lives in another DB).
     */
    public function up(): void
    {
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('number')->unique();
            $table->string('source');
            $table->string('status');
            $table->string('applicant_type');

            $table->string('signature_product_id');
            $table->string('product_name');
            $table->string('validity');
            $table->string('container');
            $table->decimal('sale_price', 10, 2)->nullable();

            $table->string('first_names');
            $table->string('first_surname');
            $table->string('second_surname')->nullable();
            $table->string('document_type');
            $table->string('document_number');
            $table->string('fingerprint_code')->nullable();
            $table->string('personal_ruc')->nullable();
            $table->string('gender');
            $table->date('birth_date');
            $table->string('nationality');
            $table->string('mobile_phone');
            $table->string('landline_phone')->nullable();
            $table->string('email');
            $table->string('province');
            $table->string('city');
            $table->string('address', 100);

            $table->string('company_name')->nullable();
            $table->string('company_ruc')->nullable();
            $table->string('position')->nullable();
            $table->string('legal_representative_first_names')->nullable();
            $table->string('legal_representative_surnames')->nullable();
            $table->string('legal_representative_document_type')->nullable();
            $table->string('legal_representative_document_number')->nullable();

            $table->string('provider_token')->nullable()->index();
            $table->string('central_reference')->nullable();
            $table->text('last_error')->nullable();
            $table->string('certificate_serial')->nullable();
            $table->timestamp('certificate_valid_from')->nullable();
            $table->timestamp('certificate_valid_to')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('document_number');
        });

        Schema::create('signature_request_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_request_id')->constrained('signature_requests')->cascadeOnDelete();
            $table->string('kind');
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->unique(['signature_request_id', 'kind']);
        });

        Schema::create('signature_request_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('signature_request_id')->constrained('signature_requests')->cascadeOnDelete();
            $table->string('status');
            $table->string('description');
            $table->string('actor_name')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('signature_storefronts', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_published')->default(false);
            $table->string('headline');
            $table->text('description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->json('prices')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_storefronts');
        Schema::dropIfExists('signature_request_events');
        Schema::dropIfExists('signature_request_documents');
        Schema::dropIfExists('signature_requests');
    }
};
