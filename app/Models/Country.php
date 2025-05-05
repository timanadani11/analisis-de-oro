<?php

namespace App\Models;

use App\Models\League;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'flag',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get all leagues for this country
     */
    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }
}
