<?php

namespace App\Services;

use App\Models\Location;
use App\Models\WeatherCache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * The base URL for the Open-Meteo API.
     *
     * @var string
     */
    protected $apiBaseUrl = 'https://api.open-meteo.com/v1';

    /**
     * The cache duration in minutes.
     *
     * @var int
     */
    protected $cacheDuration = 30;

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        $this->cacheDuration = (int) config('services.openweathermap.cache_duration', 30);
    }

    /**
     * Get current weather for a location.
     */
    public function getCurrentWeather(Location $location): ?array
    {
        // Check cache first
        $cache = $this->getCachedWeather($location, 'current');

        if ($cache) {
            return $cache->data;
        }

        try {
            $response = Http::get("{$this->apiBaseUrl}/forecast", [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m,wind_gusts_10m',
                'timezone' => 'auto',
                'temperature_unit' => 'celsius',
                'wind_speed_unit' => 'kmh',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheWeatherData($location, 'current', null, $data);

                return $data;
            }

            Log::error("Weather API error: {$response->status()} - {$response->body()}");

            return null;
        } catch (Exception $e) {
            Log::error("Weather API exception: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Get forecast for a location.
     *
     * @param  int  $days
     */
    public function getForecast(Location $location, $days = 5): ?array
    {
        // Check cache first
        $cache = $this->getCachedWeather($location, 'forecast');

        if ($cache) {
            return $cache->data;
        }

        try {
            $response = Http::get("{$this->apiBaseUrl}/forecast", [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,wind_gusts_10m_max,wind_direction_10m_dominant',
                'timezone' => 'auto',
                'forecast_days' => $days,
                'temperature_unit' => 'celsius',
                'wind_speed_unit' => 'kmh',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheWeatherData($location, 'forecast', null, $data);

                return $data;
            }

            Log::error("Weather API error: {$response->status()} - {$response->body()}");

            return null;
        } catch (Exception $e) {
            Log::error("Weather API exception: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Get weather for a specific date.
     *
     * @param  string|Carbon  $date
     * @return array|null
     */
    public function getWeatherForDate(Location $location, $date)
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        // Check if date is in the past
        if ($date->isPast() && ! $date->isToday()) {
            return $this->getHistoricalWeather($location, $date);
        }

        // If date is today, return current weather
        if ($date->isToday()) {
            return $this->getCurrentWeather($location);
        }

        // Get future forecast
        return $this->getFutureForecast($location, $date);
    }

    /**
     * Get historical weather data.
     *
     * @return array|null
     */
    protected function getHistoricalWeather(Location $location, Carbon $date)
    {
        // Check cache first
        $cache = $this->getCachedWeather($location, 'historical', $date->toDateString());

        if ($cache) {
            return $cache->data;
        }

        try {
            // Open-Meteo allows historical data access for free
            $startDate = $date->toDateString();
            $endDate = $date->copy()->addDay()->toDateString();

            $response = Http::get("{$this->apiBaseUrl}/forecast", [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,precipitation_sum,wind_speed_10m_max,wind_gusts_10m_max,wind_direction_10m_dominant',
                'timezone' => 'auto',
                'start_date' => $startDate,
                'end_date' => $startDate,
                'temperature_unit' => 'celsius',
                'wind_speed_unit' => 'kmh',
                'past_days' => 92, // Open-Meteo allows up to 92 days in the past
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheWeatherData($location, 'historical', $date->toDateString(), $data);

                return $data;
            }

            Log::error("Historical Weather API error: {$response->status()} - {$response->body()}");

            return null;
        } catch (Exception $e) {
            Log::error("Historical Weather API exception: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Get forecast for a future date.
     *
     * @return array|null
     */
    protected function getFutureForecast(Location $location, Carbon $date)
    {
        // Check cache first
        $cache = $this->getCachedWeather($location, 'forecast', $date->toDateString());

        if ($cache) {
            return $cache->data;
        }

        try {
            $startDate = $date->toDateString();
            $endDate = $date->copy()->toDateString();

            $response = Http::get("{$this->apiBaseUrl}/forecast", [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,wind_gusts_10m_max,wind_direction_10m_dominant',
                'timezone' => 'auto',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'temperature_unit' => 'celsius',
                'wind_speed_unit' => 'kmh',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheWeatherData($location, 'forecast', $date->toDateString(), $data);
                return $data;
            }

            Log::error("Future Forecast API error: {$response->status()} - {$response->body()}");
            return null;
        } catch (Exception $e) {
            Log::error("Future Forecast API exception: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get cached weather data.
     *
     * @return WeatherCache|null
     */
    protected function getCachedWeather(Location $location, string $type, ?string $date = null)
    {
        $cache = WeatherCache::where('location_id', $location->id)
            ->where('type', $type)
            ->where('date', $date)
            ->where('expires_at', '>', now())
            ->first();

        return $cache;
    }

    /**
     * Cache weather data.
     *
     * @return WeatherCache
     */
    protected function cacheWeatherData(Location $location, string $type, ?string $date, array $data)
    {
        return WeatherCache::updateOrCreate(
            [
                'location_id' => $location->id,
                'type' => $type,
                'date' => $date,
            ],
            [
                'data' => $data,
                'expires_at' => now()->addMinutes($this->cacheDuration),
            ]
        );
    }
}
