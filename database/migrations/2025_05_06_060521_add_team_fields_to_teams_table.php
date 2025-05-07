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
            // Add stadium, city, and country columns if they don't exist
            if (!Schema::hasColumn('teams', 'stadium')) {
                $table->string('stadium')->nullable()->after('logo');
            }
            
            if (!Schema::hasColumn('teams', 'city')) {
                $table->string('city')->nullable()->after('stadium');
            }
            
            if (!Schema::hasColumn('teams', 'country')) {
                $table->string('country')->nullable()->after('city');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('teams', 'stadium')) {
                $table->dropColumn('stadium');
            }
            
            if (Schema::hasColumn('teams', 'city')) {
                $table->dropColumn('city');
            }
            
            if (Schema::hasColumn('teams', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
