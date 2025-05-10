<?php

use App\Models\Country;
use App\Models\League;
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
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
        });

        // Try to migrate existing data by matching country string to country names
        $leagues = League::all();
        foreach ($leagues as $league) {
            if (!empty($league->country)) {
                $country = Country::where('name', $league->country)->first();
                if ($country) {
                    $league->country_id = $country->id;
                    $league->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
