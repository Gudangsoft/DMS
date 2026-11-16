<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema for the November 2026 feature batch:
 *  - recycle bin for categories/subcategories (soft deletes)
 *  - document validity (Berlaku / Digantikan / Dicabut) + "digantikan oleh"
 *  - expiry reminders (remember when the owner was last notified)
 *  - full-text search inside files (extracted text, kept out of `documents`
 *    so listing queries don't drag megabytes of text along)
 *  - assessor packages (shareable, expiring link to a set of documents)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('document_subcategories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('validity', 20)->default('berlaku')->after('expired_date');
            $table->foreignId('superseded_by_id')->nullable()->after('validity')
                ->constrained('documents')->nullOnDelete();
            $table->timestamp('expiry_notified_at')->nullable()->after('superseded_by_id');
            $table->index('validity');
        });

        Schema::create('document_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('content')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('assessor_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('view_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assessor_package_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessor_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['assessor_package_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_package_document');
        Schema::dropIfExists('assessor_packages');
        Schema::dropIfExists('document_contents');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropIndex(['validity']);
            $table->dropColumn(['validity', 'expiry_notified_at']);
        });

        Schema::table('document_subcategories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('document_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
