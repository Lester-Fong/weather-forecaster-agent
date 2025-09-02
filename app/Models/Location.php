<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'country',
        'latitude',
        'longitude',
        'timezone',
        'usage_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'usage_count' => 'integer',
    ];

    /**
     * Get the messages associated with this location.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the weather cache entries for this location.
     */
    public function weatherCache()
    {
        return $this->hasMany(WeatherCache::class);
    }

    /**
     * Increment the usage count for this location.
     * 
     * Skips the increment in production to avoid issues with read-only databases.
     */
    public function incrementUsage()
    {
        // Skip database writes in production to handle read-only database scenarios
        if (app()->environment('production')) {
            return;
        }

        $this->increment('usage_count');
    }
}
