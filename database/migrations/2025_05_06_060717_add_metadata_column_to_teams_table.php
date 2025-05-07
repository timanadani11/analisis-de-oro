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
        Schema::table('teams', function (Blueprint $table) {
            // Add metadata column if it doesn't exist
            if (!Schema::hasColumn('teams', 'metadata')) {
                $table->json('metadata')->nullable()->after('country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop the column if it exists
            if (Schema::hasColumn('teams', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
