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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('document_number')->nullable()->unique();
            $table->string('document_code')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->foreignId('document_category_id')
                ->constrained('document_categories')
                ->restrictOnDelete();
            $table->foreignId('document_subcategory_id')
                ->nullable()
                ->constrained('document_subcategories')
                ->nullOnDelete();
            $table->foreignId('document_type_id')
                ->constrained('document_types')
                ->restrictOnDelete();
            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('year');
            $table->date('effective_date')->nullable();
            $table->date('expired_date')->nullable();

            $table->string('confidentiality_level')->default('internal');
            $table->string('status')->default('draft');
            $table->string('current_version')->default('1.0');
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_public')->default(false);

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('title');
            $table->index('year');
            $table->index('status');
            $table->index('unit_id');
            $table->index('document_category_id');
            $table->index('document_subcategory_id');
            $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
