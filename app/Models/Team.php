<?php

namespace App\Models;

use App\Models\FootballMatch;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'api_team_id',
        'stadium',
        'city',
        'country',
        'league_id',
        'metadata',
        'founded',
        'venue_name',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the league that this team belongs to
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get all home matches for this team
     */
    public function homeMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    /**
     * Get all away matches for this team
     */
    public function awayMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }

    /**
     * Get all statistics for this team
     */
    public function stats(): HasMany
    {
        return $this->hasMany(TeamStats::class);
    }

    /**
     * Get latest statistics for this team
     */
    public function latestStats()
    {
        return $this->hasOne(TeamStats::class)->latest();
    }

    /**
     * Get statistics for a specific season
     */
    public function statsBySeason($season)
    {
        return $this->hasOne(TeamStats::class)->where('season', $season);
    }
}
