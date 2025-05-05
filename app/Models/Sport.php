<?php

namespace App\Models;

use App\Models\FootballMatch;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_sport_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get all leagues for this sport
     */
    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }

    /**
     * Get all matches for this sport
     */
    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }
}
