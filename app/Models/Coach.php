<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coach extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_coach_id',
        'team_id',
        'name',
        'first_name',
        'last_name',
        'date_of_birth',
        'nationality',
        'contract_start',
        'contract_until',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the team that the coach belongs to
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
