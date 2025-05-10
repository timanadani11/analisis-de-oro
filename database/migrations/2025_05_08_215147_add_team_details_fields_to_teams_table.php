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
            // Nuevos campos según la API de football-data.org
            $table->string('short_name')->nullable()->after('name');
            $table->string('tla', 3)->nullable()->after('short_name');
            $table->string('address')->nullable()->after('country');
            $table->string('website')->nullable()->after('address');
            $table->string('club_colors')->nullable()->after('website');
            $table->decimal('market_value', 20, 2)->nullable()->after('club_colors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'short_name',
                'tla',
                'address',
                'website',
                'club_colors',
                'market_value',
            ]);
        });
    }
};
