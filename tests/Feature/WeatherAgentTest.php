<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherAgentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the weather agent API endpoint works.
     */
    public function test_weather_agent_api_endpoint(): void
    {
        $this->withoutMiddleware();

        // Mock the HTTP responses
        Http::fake([
            // Mock the geocoding API response
            'api.openweathermap.org/geo/1.0/direct*' => Http::response([
                [
                    'name' => 'London',
                    'country' => 'GB',
                    'lat' => 51.5074,
                    'lon' => -0.1278,
                ],
            ], 200),

            // Mock the current weather API response
            'api.openweathermap.org/data/2.5/weather*' => Http::response([
                'weather' => [
                    [
                        'main' => 'Clear',
                        'description' => 'clear sky',
                    ],
                ],
                'main' => [
                    'temp' => 20.5,
                    'feels_like' => 19.8,
                    'temp_min' => 18.3,
                    'temp_max' => 22.7,
                    'humidity' => 65,
                ],
                'wind' => [
                    'speed' => 3.6,
                    'deg' => 230,
                ],
                'name' => 'London',
            ], 200),

            // Mock any other requests
            '*' => Http::response([], 404),
        ]);

        // Make a request to the API endpoint
        $response = $this->postJson('/api/weather/query', [
            'query' => 'What is the weather like in London today?',
            'session_id' => 'test-session',
        ]);

        // Assert the API response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'session_id',
                'conversation_id',
                'status',
            ]);

        // Test the conversation endpoint
        $conversationResponse = $this->getJson('/api/weather/conversation?session_id=test-session');

        $conversationResponse->assertStatus(200)
            ->assertJsonStructure([
                'conversation_id',
                'session_id',
                'messages' => [
                    '*' => [
                        'id',
                        'content',
                        'is_user',
                        'timestamp',
                    ],
                ],
            ]);
    }
}
