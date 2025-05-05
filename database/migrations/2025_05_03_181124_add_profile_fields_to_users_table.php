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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name')->nullable();
            $table->string('profile_photo')->nullable()->after('email');
            $table->enum('role', ['user', 'admin'])->default('user')->after('profile_photo');
            $table->boolean('is_premium')->default(false)->after('role');
            $table->text('bio')->nullable()->after('is_premium');
            $table->date('birth_date')->nullable()->after('bio');
            $table->string('favorite_sport')->nullable()->after('birth_date');
            $table->string('favorite_team')->nullable()->after('favorite_sport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'profile_photo',
                'role',
                'is_premium',
                'bio',
                'birth_date',
                'favorite_sport',
                'favorite_team',
            ]);
        });
    }
};
