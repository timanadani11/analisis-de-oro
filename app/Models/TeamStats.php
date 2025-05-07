<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamStats extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'league_id',
        'season',
        'stats_json',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stats_json' => 'array',
    ];

    /**
     * Get the team that owns the stats.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the league that owns the stats.
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }
} 