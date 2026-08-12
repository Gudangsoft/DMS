<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simple CMS table for institutional content pages (Sambutan Ketua, Tentang
     * DMS, Struktur Organisasi, Tugas & Fungsi) shown in the public site's
     * navbar — editable by Super Admin / Admin Dokumen instead of hardcoded Blade.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->unsignedInteger('menu_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
