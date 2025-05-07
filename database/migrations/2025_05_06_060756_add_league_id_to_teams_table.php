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
            // Add league_id column if it doesn't exist
            if (!Schema::hasColumn('teams', 'league_id')) {
                // Add as optional foreign key since teams can belong to multiple leagues
                $table->unsignedBigInteger('league_id')->nullable()->after('id');
                
                // Add foreign key constraint but allow it to be null
                $table->foreign('league_id')->references('id')->on('leagues')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop foreign key first if it exists
            if (Schema::hasColumn('teams', 'league_id')) {
                $table->dropForeign(['league_id']);
                $table->dropColumn('league_id');
            }
        });
    }
};
