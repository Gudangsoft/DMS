<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Navigation is now fully owned by MenuItem — these Page columns are no
     * longer read anywhere (superseded, not supplemented).
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['menu_order', 'show_in_menu']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->unsignedInteger('menu_order')->default(0);
            $table->boolean('show_in_menu')->default(true);
        });
    }
};
