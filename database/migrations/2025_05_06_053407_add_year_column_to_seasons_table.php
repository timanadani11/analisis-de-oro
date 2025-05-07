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
        Schema::table('seasons', function (Blueprint $table) {
            // Add year column if it doesn't exist
            if (!Schema::hasColumn('seasons', 'year')) {
                $table->integer('year')->nullable()->after('name');
            }
            
            // Add active and current columns if they don't exist
            if (!Schema::hasColumn('seasons', 'active')) {
                $table->boolean('active')->default(true)->after('end_date');
            }
            
            if (!Schema::hasColumn('seasons', 'current')) {
                $table->boolean('current')->default(false)->after('year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('seasons', 'year')) {
                $table->dropColumn('year');
            }
            
            if (Schema::hasColumn('seasons', 'active')) {
                $table->dropColumn('active');
            }
            
            if (Schema::hasColumn('seasons', 'current')) {
                $table->dropColumn('current');
            }
        });
    }
};
