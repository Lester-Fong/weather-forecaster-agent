<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LocationService $locationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locationService = new LocationService();
    }

    public function test_search_locations_returns_search_results()
    {
        // Mock API response
        $mockApiResponse = [
            'results' => [
                [
                    'name' => 'New York',
                    'country' => 'United States',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'timezone' => 'America/New_York',
                    'population' => 8804190,
                    'elevation' => 10,
                    'feature_code' => 'PPLA',
                    'country_code' => 'US',
                    'admin1' => 'New York',
                    'admin2' => 'New York County',
                    'admin3' => null,
                    'admin4' => null,
                ],
                [
                    'name' => 'New Orleans',
                    'country' => 'United States',
                    'latitude' => 29.9511,
                    'longitude' => -90.0715,
                    'timezone' => 'America/Chicago',
                    'population' => 383997,
                    'elevation' => 2,
                    'feature_code' => 'PPLA',
                    'country_code' => 'US',
                    'admin1' => 'Louisiana',
                    'admin2' => 'Orleans Parish',
                    'admin3' => null,
                    'admin4' => null,
                ],
            ],
        ];

        Http::fake([
            'geocoding-api.open-meteo.com/v1/search*' => Http::response($mockApiResponse, 200),
        ]);

        // Search for locations
        $results = $this->locationService->searchLocations('New');

        // Assert we got the expected results
        $this->assertCount(2, $results);
        $this->assertEquals('New York', $results[0]['name']);
        $this->assertEquals('United States', $results[0]['country']);
        $this->assertEquals('New Orleans', $results[1]['name']);
    }

    public function test_find_or_create_location_returns_existing_location()
    {
        // Create a location
        $existingLocation = Location::factory()->create([
            'name' => 'London',
            'country' => 'United Kingdom',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);

        // Get or create location
        $location = $this->locationService->findOrCreateLocation(
            'London',
            'United Kingdom',
            51.5074,
            -0.1278
        );

        // Assert we got the existing location
        $this->assertEquals($existingLocation->id, $location->id);
        $this->assertEquals('London', $location->name);
        $this->assertEquals('United Kingdom', $location->country);
    }

    public function test_find_or_create_location_creates_new_location_if_not_exists()
    {
        // Get or create location with data that doesn't exist yet
        $location = $this->locationService->findOrCreateLocation(
            'Paris',
            'France',
            48.8566,
            2.3522,
            'Europe/Paris'
        );

        // Assert a new location was created
        $this->assertEquals('Paris', $location->name);
        $this->assertEquals('France', $location->country);
        $this->assertEquals(48.8566, $location->latitude);
        $this->assertEquals(2.3522, $location->longitude);

        // Assert the location was saved to the database
        $this->assertDatabaseHas('locations', [
            'name' => 'Paris',
            'country' => 'France',
        ]);
    }

    public function test_reverse_geocode_returns_location_data()
    {
        // Mock API response for Open-Meteo
        $mockOpenMeteoResponse = [
            'results' => [
                [
                    'name' => 'Stockholm',
                    'country' => 'Sweden',
                    'latitude' => 59.3293,
                    'longitude' => 18.0686,
                    'timezone' => 'Europe/Stockholm',
                    'population' => 1515017,
                    'elevation' => 15,
                    'feature_code' => 'PPLA',
                    'country_code' => 'SE',
                    'admin1' => 'Stockholm',
                    'admin2' => 'Stockholm',
                ],
            ],
        ];

        Http::fake([
            'geocoding-api.open-meteo.com/v1/search*' => Http::response($mockOpenMeteoResponse, 200),
            'api.bigdatacloud.net/data/reverse-geocode-client*' => Http::response([], 404), // Not used
        ]);

        // Reverse geocode
        $result = $this->locationService->reverseGeocode(59.3293, 18.0686);

        // Assert we got the expected data
        $this->assertCount(1, $result);
        $this->assertEquals('Stockholm', $result[0]['name']);
        $this->assertEquals('Sweden', $result[0]['country']);
    }

    public function test_reverse_geocode_falls_back_to_bigdatacloud_if_openmeteo_fails()
    {
        // Mock API responses
        Http::fake([
            'geocoding-api.open-meteo.com/v1/search*' => Http::response(['results' => []], 200), // Empty results
            'api.bigdatacloud.net/data/reverse-geocode-client*' => Http::response([
                'city' => 'Tokyo',
                'locality' => 'Shinjuku',
                'countryName' => 'Japan',
                'latitude' => 35.6762,
                'longitude' => 139.6503,
                'principalSubdivision' => 'Tokyo',
                'countryCode' => 'JP',
            ], 200),
        ]);

        // Reverse geocode
        $result = $this->locationService->reverseGeocode(35.6762, 139.6503);

        // Assert we got the expected data from BigDataCloud
        $this->assertCount(1, $result);
        $this->assertEquals('Tokyo', $result[0]['name']);
        $this->assertEquals('Japan', $result[0]['country']);
    }
}
