<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_player_id',
        'team_id',
        'name',
        'first_name',
        'last_name',
        'position',
        'date_of_birth',
        'nationality',
        'shirt_number',
        'market_value',
        'contract_start',
        'contract_until',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'market_value' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the team that owns the player
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
