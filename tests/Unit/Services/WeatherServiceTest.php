<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\WeatherCache;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weatherService = new WeatherService();
    }

    public function test_get_current_weather_returns_cached_data_if_available()
    {
        // Create a location
        $location = Location::factory()->create([
            'name' => 'Test City',
            'country' => 'Test Country',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Create cached weather data
        $cachedData = [
            'temperature' => 20.5,
            'weather_code' => 0,
            'wind_speed' => 5.7,
            'time' => '2025-09-01T12:00',
        ];

        WeatherCache::factory()->create([
            'location_id' => $location->id,
            'type' => 'current',
            'date' => Carbon::now(),
            'data' => $cachedData,
            'expires_at' => Carbon::now()->addMinutes(30),
        ]);

        // Get current weather
        $result = $this->weatherService->getCurrentWeather($location);

        // Assert that we got the cached data
        $this->assertEquals($cachedData, $result);
    }

    public function test_get_current_weather_fetches_fresh_data_when_cache_expired()
    {
        // Create a location
        $location = Location::factory()->create([
            'name' => 'Test City',
            'country' => 'Test Country',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Create expired cached weather data
        $cachedData = [
            'temperature' => 20.5,
            'weather_code' => 0,
            'wind_speed' => 5.7,
            'time' => '2025-09-01T12:00',
        ];

        WeatherCache::factory()->create([
            'location_id' => $location->id,
            'type' => 'current',
            'date' => Carbon::now(),
            'data' => $cachedData,
            'expires_at' => Carbon::now()->subMinutes(5), // Expired 5 minutes ago
        ]);

        // Mock API response
        $mockApiResponse = [
            'current' => [
                'temperature_2m' => 22.5,
                'weather_code' => 1,
                'wind_speed_10m' => 6.7,
                'time' => '2025-09-01T13:00',
            ],
            'current_units' => [
                'temperature_2m' => '°C',
                'wind_speed_10m' => 'km/h',
            ],
        ];

        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::response($mockApiResponse, 200),
        ]);

        // Get current weather
        $result = $this->weatherService->getCurrentWeather($location);

        // Assert that we got fresh data, not the cached data
        $this->assertNotEquals($cachedData, $result);
        $this->assertEquals(22.5, $result['temperature']);
        $this->assertEquals(1, $result['weather_code']);
        $this->assertEquals(6.7, $result['wind_speed']);
    }

    public function test_get_forecast_returns_forecast_data()
    {
        // Create a location
        $location = Location::factory()->create([
            'name' => 'Test City',
            'country' => 'Test Country',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Mock API response for forecast
        $mockApiResponse = [
            'daily' => [
                'time' => ['2025-09-01', '2025-09-02', '2025-09-03'],
                'temperature_2m_max' => [25.5, 26.2, 24.8],
                'temperature_2m_min' => [15.5, 16.2, 14.8],
                'weather_code' => [0, 1, 2],
                'precipitation_probability_max' => [0, 20, 40],
            ],
            'daily_units' => [
                'temperature_2m_max' => '°C',
                'temperature_2m_min' => '°C',
                'precipitation_probability_max' => '%',
            ],
        ];

        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::response($mockApiResponse, 200),
        ]);

        // Get forecast
        $result = $this->weatherService->getForecast($location, 3);

        // Assert we got the expected data
        $this->assertCount(3, $result);
        $this->assertEquals('2025-09-01', $result[0]['date']);
        $this->assertEquals(25.5, $result[0]['max_temp']);
        $this->assertEquals(15.5, $result[0]['min_temp']);
        $this->assertEquals(0, $result[0]['weather_code']);
    }

    public function test_get_weather_for_date_returns_data_for_specific_date()
    {
        // Create a location
        $location = Location::factory()->create([
            'name' => 'Test City',
            'country' => 'Test Country',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Create a date (tomorrow)
        $date = Carbon::tomorrow();
        $dateString = $date->format('Y-m-d');

        // Mock API response
        $mockApiResponse = [
            'daily' => [
                'time' => [$dateString],
                'temperature_2m_max' => [24.5],
                'temperature_2m_min' => [14.5],
                'weather_code' => [1],
                'precipitation_probability_max' => [30],
            ],
            'daily_units' => [
                'temperature_2m_max' => '°C',
                'temperature_2m_min' => '°C',
                'precipitation_probability_max' => '%',
            ],
        ];

        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::response($mockApiResponse, 200),
        ]);

        // Get weather for the specific date
        $result = $this->weatherService->getWeatherForDate($location, $date);

        // Assert we got the expected data
        $this->assertEquals($dateString, $result['date']);
        $this->assertEquals(24.5, $result['max_temp']);
        $this->assertEquals(14.5, $result['min_temp']);
        $this->assertEquals(1, $result['weather_code']);
        $this->assertEquals(30, $result['precipitation_probability']);
    }

    public function test_weather_cache_works_correctly()
    {
        // Create a location
        $location = Location::factory()->create([
            'name' => 'Test City',
            'country' => 'Test Country',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Mock API response for current weather
        $mockApiResponse = [
            'current' => [
                'temperature_2m' => 22.5,
                'weather_code' => 1,
                'wind_speed_10m' => 6.7,
                'time' => '2025-09-01T13:00',
            ],
            'current_units' => [
                'temperature_2m' => '°C',
                'wind_speed_10m' => 'km/h',
            ],
        ];

        Http::fake([
            'api.open-meteo.com/v1/forecast*' => Http::response($mockApiResponse, 200),
        ]);

        // First call should create a cache entry
        $result1 = $this->weatherService->getCurrentWeather($location);

        // Verify that a cache entry was created
        $cachedData = WeatherCache::where('location_id', $location->id)
            ->where('type', 'current')
            ->first();

        $this->assertNotNull($cachedData);
        $this->assertEquals($result1, $cachedData->data);

        // Second call should use the cache
        $result2 = $this->weatherService->getCurrentWeather($location);

        // Results should be the same
        $this->assertEquals($result1, $result2);

        // Verify the API was only called once
        Http::assertSentCount(1);
    }
}
