<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FootballMatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'football_matches';

    protected $fillable = [
        'league_id',
        'home_team_id',
        'away_team_id',
        'api_fixture_id',
        'match_date',
        'status',
        'home_goals',
        'away_goals',
        'referee',
        'venue',
        'elapsed_time',
        'statistics',
        'events',
        'lineups',
        'round',
        'is_analyzed',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'home_goals' => 'integer',
        'away_goals' => 'integer',
        'elapsed_time' => 'integer',
        'statistics' => 'array',
        'events' => 'array',
        'lineups' => 'array',
        'is_analyzed' => 'boolean',
    ];

    /**
     * Get the league that owns the match
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the home team
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Get the season that this match belongs to.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Scope a query to only include matches for today and tomorrow
     */
    public function scopeUpcoming($query)
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->endOfDay();
        
        return $query->whereBetween('match_date', [$today, $tomorrow])
                     ->orderBy('match_date', 'asc');
    }

    /**
     * Scope a query to only include matches for a specific date
     */
    public function scopeForDate($query, $date)
    {
        $startDate = Carbon::parse($date)->startOfDay();
        $endDate = Carbon::parse($date)->endOfDay();
        
        return $query->whereBetween('match_date', [$startDate, $endDate])
                     ->orderBy('match_date', 'asc');
    }
}
