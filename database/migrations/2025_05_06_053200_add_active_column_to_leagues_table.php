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
            // Add active column if it doesn't exist
            if (!Schema::hasColumn('leagues', 'active')) {
                $table->boolean('active')->default(true)->after('api_league_id');
            }
            
            // Add country column if it doesn't exist
            if (!Schema::hasColumn('leagues', 'country')) {
                $table->string('country')->nullable()->after('logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('leagues', 'active')) {
                $table->dropColumn('active');
            }
            
            if (Schema::hasColumn('leagues', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
