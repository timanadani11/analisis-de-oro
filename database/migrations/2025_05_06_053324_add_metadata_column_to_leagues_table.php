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
        Schema::table('leagues', function (Blueprint $table) {
            // Add metadata column if it doesn't exist
            if (!Schema::hasColumn('leagues', 'metadata')) {
                $table->json('metadata')->nullable()->after('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Drop the column if it exists
            if (Schema::hasColumn('leagues', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
