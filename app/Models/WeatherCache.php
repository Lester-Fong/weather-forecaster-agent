<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherCache extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'weather_cache';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'location_id',
        'type',
        'date',
        'data',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'date' => 'date',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the location associated with this weather cache entry.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Check if the cached data is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return now()->gt($this->expires_at);
    }
}
