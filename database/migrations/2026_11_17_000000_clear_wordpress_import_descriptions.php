<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Documents imported from the old WordPress DMS carried an internal note as
 * their description ("Diimpor dari WordPress DMS lama — sumber: …"), which
 * showed up on every public card. Clear it; cards now fall back to a summary
 * built from the document's metadata (Document::summary()). Admin-written
 * descriptions are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')
            ->where('description', 'like', 'Diimpor dari WordPress DMS lama%')
            ->update(['description' => null]);
    }

    public function down(): void
    {
        // The old text was a generated import note; nothing worth restoring.
    }
};
