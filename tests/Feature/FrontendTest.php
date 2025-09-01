<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendTest extends TestCase
{
    /**
     * Test that the main page loads correctly.
     */
    public function test_main_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Weather Forecaster', false);
    }

    /**
     * Test that the API endpoint for weather queries is accessible.
     */
    public function test_weather_query_api_endpoint_is_accessible(): void
    {
        $response = $this->postJson('/api/weather/query', [
            'query' => 'Test query',
            'session_id' => 'test_session',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test that the API endpoint for location detection is accessible.
     */
    public function test_location_detection_api_endpoint_is_accessible(): void
    {
        $response = $this->postJson('/api/weather/detect-location', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'session_id' => 'test_session',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success']);
    }

    /**
     * Test that JavaScript bundle is properly loaded.
     */
    public function test_javascript_bundle_is_loaded(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Check if Vite has properly injected script tags
        $response->assertSee('id="app"', false);
    }

    /**
     * Test performance by measuring response time.
     */
    public function test_performance_of_main_page(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/');

        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;

        $response->assertStatus(200);

        // The page should load in less than 1 second
        $this->assertLessThan(1.0, $loadTime);
    }
}
