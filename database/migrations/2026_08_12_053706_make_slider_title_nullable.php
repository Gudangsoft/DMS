<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A slide is allowed to be image-only — no caption required.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE sliders MODIFY title VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE sliders SET title = '' WHERE title IS NULL");
        DB::statement('ALTER TABLE sliders MODIFY title VARCHAR(255) NOT NULL');
    }
};
