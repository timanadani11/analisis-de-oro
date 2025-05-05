<?php

namespace App\Models;

use App\Models\FootballMatch;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'name',
        'year',
        'start_date',
        'end_date',
        'current',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'current' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Get the league that owns the season
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get all matches for this season
     */
    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }
}
