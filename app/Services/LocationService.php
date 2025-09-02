<?php

namespace App\Services;

use App\Models\Location;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * The base URL for the Open-Meteo geocoding API.
     *
     * @var string
     */
    protected $apiBaseUrl = 'https://geocoding-api.open-meteo.com/v1';

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        // No API key needed for Open-Meteo
    }

    /**
     * Reverse geocode coordinates to find location.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array|null
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        try {
            // First try Open-Meteo API
            $result = $this->tryOpenMeteoReverseGeocode($latitude, $longitude);

            // If Open-Meteo failed, try BigDataCloud as a backup
            if (empty($result)) {
                $result = $this->tryBigDataCloudReverseGeocode($latitude, $longitude);
            }

            // If both failed, create a generic location
            if (empty($result)) {
                Log::info("Using generic location for coordinates: {$latitude}, {$longitude}");
                return [
                    [
                        'name' => 'Your Location',
                        'country' => 'Current Position',
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'timezone' => 'UTC',
                    ]
                ];
            }

            return $result;
        } catch (Exception $e) {
            Log::error("Reverse geocoding API exception: {$e->getMessage()}");

            // Return a generic location on exception
            return [
                [
                    'name' => 'Your Location',
                    'country' => 'Current Position',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'timezone' => 'UTC',
                ]
            ];
        }
    }

    /**
     * Try to reverse geocode using Open-Meteo API.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    private function tryOpenMeteoReverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $response = Http::get("{$this->apiBaseUrl}/search", [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'count' => 1,
                'language' => 'en',
                'format' => 'json',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::debug('Open-Meteo reverse geocode response: ' . json_encode($data));

                $results = $data['results'] ?? [];

                if (empty($results)) {
                    Log::warning("No locations found from Open-Meteo for coordinates: {$latitude}, {$longitude}");
                    return [];
                }

                // Transform to match the expected format
                $locations = [];
                foreach ($results as $result) {
                    $locations[] = [
                        'name' => $result['name'] ?? 'Unknown',
                        'country' => $result['country'] ?? 'Unknown',
                        'lat' => $result['latitude'],
                        'lon' => $result['longitude'],
                        'timezone' => $result['timezone'] ?? 'UTC',
                    ];
                }
                return $locations;
            }

            Log::error("Open-Meteo reverse geocoding API error: {$response->status()} - {$response->body()}");
            return [];
        } catch (Exception $e) {
            Log::error("Open-Meteo reverse geocoding exception: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Try to reverse geocode using BigDataCloud API.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    private function tryBigDataCloudReverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $response = Http::get("https://api.bigdatacloud.net/data/reverse-geocode-client", [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'localityLanguage' => 'en',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::debug('BigDataCloud reverse geocode response: ' . json_encode($data));

                if (empty($data['city']) && empty($data['locality'])) {
                    Log::warning("No locations found from BigDataCloud for coordinates: {$latitude}, {$longitude}");
                    return [];
                }

                // Use the most specific location name available
                $name = $data['city'] ?? $data['locality'] ?? $data['principalSubdivision'] ?? 'Unknown Location';

                return [
                    [
                        'name' => $name,
                        'country' => $data['countryName'] ?? 'Unknown',
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'timezone' => 'UTC', // BigDataCloud doesn't provide timezone
                    ]
                ];
            }

            Log::error("BigDataCloud reverse geocoding API error: {$response->status()} - {$response->body()}");
            return [];
        } catch (Exception $e) {
            Log::error("BigDataCloud reverse geocoding exception: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Search for locations by query.
     * 
     * @param string $query The location search query
     * @param int $limit Maximum number of results to return
     * @param string|null $country Optional country filter
     * @return array|null
     */
    public function searchLocations(string $query, int $limit = 5, ?string $country = null): ?array
    {
        try {
            $params = [
                'name' => $query,
                'count' => $limit * 2, // Request more results to account for filtering
                'language' => 'en',
                'format' => 'json',
            ];

            // Add country filter if provided
            if ($country) {
                $params['country'] = $country;
            }

            $response = Http::get("{$this->apiBaseUrl}/search", $params);

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];

                // Log the search results for debugging
                Log::debug('Location search results for "' . $query . '": ' . json_encode($results));

                // Transform to match the expected format
                $locations = [];
                foreach ($results as $result) {
                    $locations[] = [
                        'name' => $result['name'],
                        'country' => $result['country'],
                        'lat' => $result['latitude'],
                        'lon' => $result['longitude'],
                        'timezone' => $result['timezone'],
                    ];
                }
                return $locations;
            }

            Log::error("Geocoding API error: {$response->status()} - {$response->body()}");

            return [];
        } catch (Exception $e) {
            Log::error("Geocoding API exception: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Find or create a location.
     */
    public function findOrCreateLocation(
        string $name,
        string $country,
        float $latitude,
        float $longitude,
        ?string $timezone = null
    ): Location {
        // Round coordinates to 4 decimal places for better matching
        $latitude = round($latitude, 4);
        $longitude = round($longitude, 4);

        // First try to find by exact coordinates (within a small radius)
        $location = Location::where('latitude', $latitude)
            ->where('longitude', $longitude)
            ->first();

        if (! $location) {
            // Then try to find by name and country
            $location = Location::where('name', $name)
                ->where('country', $country)
                ->first();
        }

        if (! $location) {
            // In production, we should avoid writes to the database if it's read-only
            if (app()->environment('production')) {
                // Create a non-persisted location object with default values
                $location = new Location([
                    'name' => $name,
                    'country' => $country,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'timezone' => $timezone ?? 'UTC',
                    'usage_count' => 1,
                ]);
                // Set exists to false to indicate it's not from database
                $location->exists = false;
            } else {
                // Create a new location if none found (development environment)
                $location = Location::create([
                    'name' => $name,
                    'country' => $country,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'timezone' => $timezone ?? 'UTC',
                    'usage_count' => 1,
                ]);
            }
        } else {
            // Increment usage count
            $location->incrementUsage();
        }

        return $location;
    }

    /**
     * Get timezone for coordinates.
     */
    public function getTimezone(float $latitude, float $longitude): string
    {
        try {
            // Using TimezoneDB API as an example
            $apiKey = config('services.timezonedb.key', 'demo');
            $response = Http::get('https://api.timezonedb.com/v2.1/get-time-zone', [
                'key' => $apiKey,
                'format' => 'json',
                'by' => 'position',
                'lat' => $latitude,
                'lng' => $longitude,
            ]);

            if ($response->successful() && isset($response['zoneName'])) {
                return $response['zoneName'];
            }
        } catch (Exception $e) {
            Log::error("Timezone API exception: {$e->getMessage()}");
        }

        // Default to UTC if API fails
        return 'UTC';
    }
}
