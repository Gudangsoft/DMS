<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A version can now come from either an uploaded file or a link to a file
     * hosted elsewhere (e.g. a regulation already published on a ministry
     * site). Link-sourced versions have no local bytes, so file_path,
     * file_size, mime_type, and checksum become nullable — SHA-256 integrity
     * verification (poin 43) only applies to uploads, never to links.
     */
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->string('source_type', 20)->default('upload')->after('document_id');
            $table->string('external_url', 2048)->nullable()->after('file_path');
        });

        DB::statement('ALTER TABLE document_versions MODIFY file_path VARCHAR(255) NULL');
        DB::statement('ALTER TABLE document_versions MODIFY file_size BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE document_versions MODIFY mime_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE document_versions MODIFY checksum VARCHAR(64) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE document_versions SET file_path = '' WHERE file_path IS NULL");
        DB::statement("UPDATE document_versions SET file_size = 0 WHERE file_size IS NULL");
        DB::statement("UPDATE document_versions SET mime_type = 'application/octet-stream' WHERE mime_type IS NULL");
        DB::statement("UPDATE document_versions SET checksum = '' WHERE checksum IS NULL");

        DB::statement('ALTER TABLE document_versions MODIFY file_path VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE document_versions MODIFY file_size BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE document_versions MODIFY mime_type VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE document_versions MODIFY checksum VARCHAR(64) NOT NULL');

        Schema::table('document_versions', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'external_url']);
        });
    }
};
