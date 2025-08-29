<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\WeatherQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WeatherAgentController extends Controller
{
    /**
     * The weather query service instance.
     *
     * @var \App\Services\WeatherQueryService
     */
    protected $weatherQueryService;

    /**
     * Create a new controller instance.
     */
    public function __construct(WeatherQueryService $weatherQueryService)
    {
        $this->weatherQueryService = $weatherQueryService;
    }

    /**
     * Process a weather query.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function query(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500',
            'session_id' => 'nullable|string|max:100',
            'location_id' => 'nullable|integer', // Add validation for location_id
        ]);

        // Get or create the conversation
        $sessionId = $request->input('session_id', Str::uuid()->toString());
        $conversation = Conversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        // Save the user message
        $userMessage = new Message([
            'content' => $request->input('query'),
            'is_user' => true,
            'location_id' => $request->input('location_id'), // Save location_id with user message
        ]);
        $conversation->messages()->save($userMessage);

        // Process the query with location_id if provided
        $response = $this->weatherQueryService->processQuery(
            $request->input('query'),
            $conversation,
            $request->input('location_id')
        );

        // Save the response message
        $responseMessage = new Message([
            'content' => $response['message'],
            'is_user' => false,
            'location_id' => $response['location'] ? $response['location']->id : null,
            'metadata' => [
                'query_type' => $response['query_type'],
                'date_info' => $response['date_info'],
                'data' => $response['data'],
            ],
        ]);
        $conversation->messages()->save($responseMessage);

        // Extract essential weather data for the UI
        $weatherData = null;
        if ($response['data']) {
            if (isset($response['data']['current'])) {
                // OpenMeteo format
                $weatherData = [
                    'temperature' => round($response['data']['current']['temperature_2m']),
                    'condition' => $this->getWeatherCondition($response['data']),
                ];
            } elseif (isset($response['data']['main'])) {
                // OpenWeatherMap format
                $weatherData = [
                    'temperature' => round($response['data']['main']['temp']),
                    'condition' => $response['data']['weather'][0]['description'] ?? null,
                ];
            }
        }

        // Format date info for display
        $formattedDateInfo = null;
        if ($response['date_info']) {
            if ($response['date_info']['type'] === 'default') {
                $formattedDateInfo = 'Today';
            } else {
                $formattedDateInfo = $response['date_info']['formatted'] ?? $response['date_info']['text'] ?? null;
            }
        }

        return response()->json([
            'message' => $response['message'],
            'session_id' => $sessionId,
            'conversation_id' => $conversation->id,
            'status' => $response['status'],
            'metadata' => [
                'location' => $response['location'] ? $response['location']->name . ', ' . $response['location']->country : null,
                'weather' => $weatherData,
                'date' => $formattedDateInfo,
                // Debug info is stored in DB but not sent to frontend
            ],
        ]);
    }

    /**
     * Get conversation history.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get weather condition text from weather data
     * 
     * @param array $weatherData
     * @return string|null
     */
    protected function getWeatherCondition(array $weatherData): ?string
    {
        // Check for OpenMeteo format
        if (isset($weatherData['current']) && isset($weatherData['current']['weather_code'])) {
            $weatherCode = $weatherData['current']['weather_code'];
            return $this->getWeatherConditionFromCode($weatherCode);
        }

        // Check for OpenWeatherMap format
        if (isset($weatherData['weather']) && isset($weatherData['weather'][0]['description'])) {
            return ucfirst($weatherData['weather'][0]['description']);
        }

        return null;
    }

    /**
     * Get weather condition text from OpenMeteo weather code
     * 
     * @param int $code
     * @return string
     */
    protected function getWeatherConditionFromCode(int $code): string
    {
        // Weather codes from OpenMeteo API
        switch ($code) {
            case 0:
                return 'Clear sky';
            case 1:
                return 'Mainly clear';
            case 2:
                return 'Partly cloudy';
            case 3:
                return 'Overcast';
            case 45:
            case 48:
                return 'Fog';
            case 51:
                return 'Light drizzle';
            case 53:
                return 'Moderate drizzle';
            case 55:
                return 'Dense drizzle';
            case 56:
            case 57:
                return 'Freezing drizzle';
            case 61:
                return 'Slight rain';
            case 63:
                return 'Moderate rain';
            case 65:
                return 'Heavy rain';
            case 66:
            case 67:
                return 'Freezing rain';
            case 71:
                return 'Slight snow fall';
            case 73:
                return 'Moderate snow fall';
            case 75:
                return 'Heavy snow fall';
            case 77:
                return 'Snow grains';
            case 80:
                return 'Slight rain showers';
            case 81:
                return 'Moderate rain showers';
            case 82:
                return 'Violent rain showers';
            case 85:
            case 86:
                return 'Snow showers';
            case 95:
                return 'Thunderstorm';
            case 96:
            case 99:
                return 'Thunderstorm with hail';
            default:
                return 'Unknown conditions';
        }
    }

    /**
     * Detect user's location from coordinates.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detectLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'session_id' => 'nullable|string|max:100',
        ]);

        try {
            // Use LocationService to get location data from coordinates
            $locationService = app(\App\Services\LocationService::class);
            $locationData = $locationService->reverseGeocode(
                $request->input('latitude'),
                $request->input('longitude')
            );

            if (empty($locationData)) {
                // Log detailed info for debugging
                \Log::warning("Could not determine location from coordinates: {$request->input('latitude')}, {$request->input('longitude')}");

                // Return a user-friendly message with 200 status (not 404)
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Could not determine your location. Please specify a location in your query.',
                    'location' => null,
                ], 200);
            }            // First result is usually the most accurate
            $location = $locationData[0];

            // Create or find the location in our database
            $savedLocation = $locationService->findOrCreateLocation(
                $location['name'],
                $location['country'],
                $request->input('latitude'),
                $request->input('longitude')
            );

            // Get or create conversation if session_id provided
            if ($request->has('session_id')) {
                $sessionId = $request->input('session_id');
                $conversation = Conversation::firstOrCreate(
                    ['session_id' => $sessionId],
                    [
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
            }

            return response()->json([
                'status' => 'success',
                'location' => $savedLocation,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while detecting your location.',
            ], 500);
        }
    }

    public function getConversation(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string|max:100',
        ]);

        $conversation = Conversation::where('session_id', $request->input('session_id'))->first();

        if (! $conversation) {
            return response()->json([
                'messages' => [],
            ]);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'is_user' => $message->is_user,
                    'timestamp' => $message->created_at->toIso8601String(),
                    'location' => $message->location ? [
                        'id' => $message->location->id,
                        'name' => $message->location->name,
                        'country' => $message->location->country,
                    ] : null,
                ];
            });

        return response()->json([
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'messages' => $messages,
        ]);
    }
}
