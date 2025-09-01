<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Services\GeminiService;
use App\Services\LocationService;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherAgentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock external service calls
        $this->mock(WeatherService::class);
        $this->mock(GeminiService::class);
        $this->mock(LocationService::class);
    }

    public function test_query_endpoint_requires_authentication_or_valid_request()
    {
        // Test without required parameters
        $response = $this->postJson('/api/weather/query', []);
        $response->assertStatus(422); // Validation error

        // Test with only query parameter
        $response = $this->postJson('/api/weather/query', [
            'query' => 'What is the weather like in New York?',
        ]);
        $response->assertStatus(200); // Should be successful with just query
    }

    public function test_query_endpoint_returns_expected_format()
    {
        // Mock services to return expected data
        $this->mock(WeatherService::class, function ($mock) {
            $mock->shouldReceive('getCurrentWeather')->andReturn([
                'temperature' => 22.5,
                'weather_code' => 1,
                'wind_speed' => 5.0,
                'time' => '2025-09-01T12:00',
            ]);
        });

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('generateResponse')->andReturn('It is 22.5°C and sunny in New York right now.');
        });

        // Call the endpoint
        $response = $this->postJson('/api/weather/query', [
            'query' => 'What is the weather like in New York?',
            'session_id' => 'test_session',
        ]);

        // Assert response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'metadata' => [
                    'location',
                    'weather',
                    'date',
                ],
            ]);
    }

    public function test_detect_location_endpoint_works_correctly()
    {
        // Create a location to be returned
        $location = Location::factory()->create([
            'name' => 'San Francisco',
            'country' => 'United States',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
        ]);

        // Mock the LocationService
        $this->mock(LocationService::class, function ($mock) use ($location) {
            $mock->shouldReceive('reverseGeocode')->andReturn([
                [
                    'name' => 'San Francisco',
                    'country' => 'United States',
                    'latitude' => 37.7749,
                    'longitude' => -122.4194,
                ],
            ]);

            $mock->shouldReceive('findOrCreateLocation')->andReturn($location);
        });

        // Call the endpoint
        $response = $this->postJson('/api/weather/detect-location', [
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'session_id' => 'test_session',
        ]);

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'location' => [
                    'id' => $location->id,
                    'name' => 'San Francisco',
                    'country' => 'United States',
                ],
            ]);
    }

    public function test_detect_location_requires_coordinates()
    {
        // Call without coordinates
        $response = $this->postJson('/api/weather/detect-location', [
            'session_id' => 'test_session',
        ]);

        // Should fail validation
        $response->assertStatus(422);
    }

    public function test_handles_error_in_query_gracefully()
    {
        // Mock WeatherService to throw an exception
        $this->mock(WeatherService::class, function ($mock) {
            $mock->shouldReceive('getCurrentWeather')->andThrow(new \Exception('API error'));
        });

        // Call the endpoint
        $response = $this->postJson('/api/weather/query', [
            'query' => 'What is the weather like in New York?',
            'session_id' => 'test_session',
        ]);

        // Should return a 200 status with an error message
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'error',
            ]);
    }
}
