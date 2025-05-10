<?php

namespace App\Models;

use App\Models\FootballMatch;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'logo',
        'api_league_id',
        'country',
        'active',
        'metadata',
    ];

    protected $casts = [
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get all matches for this league
     */
    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }

    /**
     * Get the country that this league belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
