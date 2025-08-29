<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiService
{
    /**
     * The base URL for the Gemini API.
     *
     * @var string
     */
    protected $apiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * The API key for Gemini.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The model to use for queries.
     * 
     * @var string
     */
    protected $model = 'gemini-2.0-flash';

    /**
     * The cache duration in minutes.
     *
     * @var int
     */
    protected $cacheDuration = 60;

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    /**
     * Format weather data into a more readable structure for the LLM.
     * 
     * @param array $context The context containing weather data
     * @return string Formatted weather data
     */
    protected function formatWeatherDataForLLM(array $context): string
    {
        if (empty($context['weather'])) {
            return '';
        }

        $weatherData = $context['weather'];
        $formattedData = '';

        // Format current weather data
        if (isset($weatherData['current'])) {
            $current = $weatherData['current'];
            $formattedData .= "Current Weather:\n";
            $formattedData .= "- Temperature: {$current['temperature_2m']}°C\n";
            $formattedData .= "- Feels like: {$current['apparent_temperature']}°C\n";
            $formattedData .= "- Humidity: {$current['relative_humidity_2m']}%\n";
            $formattedData .= "- Wind speed: {$current['wind_speed_10m']} m/s\n";
            $formattedData .= "- Wind direction: {$current['wind_direction_10m']}°\n";

            if (isset($current['weather_code'])) {
                $weatherCondition = $this->getWeatherConditionFromCode($current['weather_code']);
                $formattedData .= "- Weather condition: {$weatherCondition}\n";
            }

            if (isset($current['precipitation'])) {
                $formattedData .= "- Precipitation: {$current['precipitation']} mm\n";
            }
        } else if (isset($weatherData['main'])) {
            // Handle OpenWeatherMap format
            $formattedData .= "Current Weather:\n";
            $formattedData .= "- Temperature: {$weatherData['main']['temp']}°C\n";
            $formattedData .= "- Feels like: {$weatherData['main']['feels_like']}°C\n";
            $formattedData .= "- Humidity: {$weatherData['main']['humidity']}%\n";
            $formattedData .= "- Wind speed: {$weatherData['wind']['speed']} m/s\n";

            if (isset($weatherData['wind']['deg'])) {
                $formattedData .= "- Wind direction: {$weatherData['wind']['deg']}°\n";
            }

            if (isset($weatherData['weather'][0]['description'])) {
                $formattedData .= "- Weather condition: " . ucfirst($weatherData['weather'][0]['description']) . "\n";
            }

            if (isset($weatherData['rain']['1h'])) {
                $formattedData .= "- Precipitation (1h): {$weatherData['rain']['1h']} mm\n";
            }
        }

        // Format forecast data
        if (isset($weatherData['daily'])) {
            $daily = $weatherData['daily'];
            $formattedData .= "\nForecast:\n";

            for ($i = 0; $i < count($daily['time'] ?? []); $i++) {
                $date = $daily['time'][$i];
                $formattedData .= "- {$date}:\n";

                if (isset($daily['temperature_2m_min'][$i], $daily['temperature_2m_max'][$i])) {
                    $minTemp = $daily['temperature_2m_min'][$i];
                    $maxTemp = $daily['temperature_2m_max'][$i];
                    $formattedData .= "  - Temperature: {$minTemp}°C to {$maxTemp}°C\n";
                }

                if (isset($daily['weathercode'][$i])) {
                    $weatherCondition = $this->getWeatherConditionFromCode($daily['weathercode'][$i]);
                    $formattedData .= "  - Conditions: {$weatherCondition}\n";
                }

                if (isset($daily['precipitation_sum'][$i])) {
                    $precipitation = $daily['precipitation_sum'][$i];
                    $formattedData .= "  - Precipitation: {$precipitation} mm\n";
                }

                if (isset($daily['wind_speed_10m_max'][$i])) {
                    $windSpeed = $daily['wind_speed_10m_max'][$i];
                    $formattedData .= "  - Max wind speed: {$windSpeed} m/s\n";
                }
            }
        } else if (isset($weatherData['list'])) {
            // Handle OpenWeatherMap forecast format
            $formattedData .= "\nForecast:\n";
            $dayForecasts = [];

            // Group by day
            foreach ($weatherData['list'] as $item) {
                $date = date('Y-m-d', $item['dt']);
                if (!isset($dayForecasts[$date])) {
                    $dayForecasts[$date] = [];
                }
                $dayForecasts[$date][] = $item;
            }

            foreach ($dayForecasts as $date => $forecasts) {
                $formattedData .= "- {$date}:\n";

                // Calculate min/max temperatures
                $minTemp = PHP_INT_MAX;
                $maxTemp = PHP_INT_MIN;
                $conditions = [];

                foreach ($forecasts as $forecast) {
                    $minTemp = min($minTemp, $forecast['main']['temp_min']);
                    $maxTemp = max($maxTemp, $forecast['main']['temp_max']);
                    $conditions[] = $forecast['weather'][0]['main'];
                }

                $formattedData .= "  - Temperature: {$minTemp}°C to {$maxTemp}°C\n";

                // Get most common condition
                $conditionCounts = array_count_values($conditions);
                arsort($conditionCounts);
                $mainCondition = key($conditionCounts);
                $formattedData .= "  - Conditions: {$mainCondition}\n";

                // Check for precipitation
                $hasRain = false;
                $hasSnow = false;
                foreach ($forecasts as $forecast) {
                    if (isset($forecast['rain'])) {
                        $hasRain = true;
                    }
                    if (isset($forecast['snow'])) {
                        $hasSnow = true;
                    }
                }

                if ($hasRain) {
                    $formattedData .= "  - Precipitation: Rain expected\n";
                }
                if ($hasSnow) {
                    $formattedData .= "  - Precipitation: Snow expected\n";
                }
            }
        }

        return $formattedData;
    }

    /**
     * Format the response text for better readability
     *
     * @param string $text The raw response text from the LLM
     * @return string Formatted text
     */
    protected function formatResponseText(string $text): string
    {
        // Ensure paragraphs have proper spacing
        $text = preg_replace('/(\r\n|\r|\n){2,}/', "\n\n", $text);
        $text = preg_replace('/([.!?])\s+/', "$1\n\n", $text);

        // Remove excessive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Ensure there are line breaks before list items
        $text = preg_replace('/([\n]?)(\d+\.\s|[•\-*]\s)/', "\n$2", $text);

        // Trim and clean up
        $text = trim($text);

        return $text;
    }

    /**
     * Get weather condition from OpenMeteo weather code.
     */
    protected function getWeatherConditionFromCode(int $code): string
    {
        // Weather codes from OpenMeteo API (https://open-meteo.com/en/docs)
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
     * Generate a response using Gemini.
     *
     * @param string $prompt The prompt to send to Gemini
     * @param array $context Additional context to include in the prompt
     * @return string|null The generated response or null if an error occurred
     */
    public function generateResponse(string $prompt, array $context = []): ?string
    {
        // Generate a cache key based on the prompt and context
        $cacheKey = 'gemini_' . md5($prompt . json_encode($context));

        // Check if we have a cached response
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Build a structured weather data object for the LLM
            $structuredWeatherData = $this->formatWeatherDataForLLM($context);

            // Build the full prompt with context and instructions
            $fullPrompt = "You are a helpful, friendly weather assistant. You communicate in a conversational style like a character from Stardew Valley.\n\n";

            // Add user's original query
            $fullPrompt .= "USER QUERY: {$prompt}\n\n";

            // Add location information if available
            if (!empty($context['location'])) {
                $fullPrompt .= "LOCATION: {$context['location']->name}, {$context['location']->country}\n\n";
            }

            // Add date information if available
            if (!empty($context['date_info'])) {
                $fullPrompt .= "DATE: {$context['date_info']['formatted']}\n";
                $fullPrompt .= "DATE TYPE: {$context['date_info']['type']} (could be 'specific', 'range', or 'default')\n";
                $fullPrompt .= "DATE TEXT: {$context['date_info']['text']}\n\n";
            }

            // Add query type if available
            if (!empty($context['query_type'])) {
                $fullPrompt .= "QUERY TYPE: {$context['query_type']} (e.g., current, forecast, temperature, precipitation, wind, humidity)\n\n";
            }

            // Add structured weather data
            if (!empty($structuredWeatherData)) {
                $fullPrompt .= "WEATHER DATA:\n{$structuredWeatherData}\n\n";
            }

            // Instructions for the LLM
            $fullPrompt .= "INSTRUCTIONS:\n";
            $fullPrompt .= "1. Respond directly to the user's query in a friendly, conversational tone.\n";
            $fullPrompt .= "2. Use the weather data provided to give accurate information.\n";
            $fullPrompt .= "3. Format your response with clear paragraphs and line breaks between different topics or sections.\n";
            $fullPrompt .= "4. Start with a greeting that mentions the location and current conditions.\n";
            $fullPrompt .= "5. Put weather details in separate paragraphs from recommendations or advice.\n";
            $fullPrompt .= "6. If the query asks about weather in the future, include phrases like 'the forecast shows' or 'it looks like'.\n";
            $fullPrompt .= "7. If the query asks about current weather, use present tense.\n";
            $fullPrompt .= "8. If the weather data doesn't contain what the user is asking for, politely mention the limitation.\n";
            $fullPrompt .= "9. Do not mention that you are an AI model or that you're using weather data provided to you.\n";
            $fullPrompt .= "10. Do not include technical terms like 'weather code' or 'API' in your response.\n";
            $fullPrompt .= "11. Respond as if you're having a direct conversation with the user.\n";
            $fullPrompt .= "12. If the user is asking about an activity (swimming, hiking, etc.), address whether the weather conditions are suitable for that activity in a separate paragraph.\n";
            $fullPrompt .= "13. If the user asks questions like 'Should I continue my plan?', provide a recommendation based on the weather conditions.\n";
            $fullPrompt .= "14. Always include real life, practical advice for the user in the final paragraph.\n";
            $fullPrompt .= "15. When referring to a city or location, ALWAYS include the country name for clarity (e.g., 'Paris, France').\n";
            $fullPrompt .= "16. Format your response with proper paragraph breaks to improve readability.\n";
            $fullPrompt .= "17. IMPORTANT: Use EXACTLY the location provided in the LOCATION field above. Do NOT switch to a similarly named location in a different country.\n";
            $fullPrompt .= "18. Be precise about the country the location is in - if the location has the same name in another country, use the user's country as default. eg: it's Santa Rosa, Philippines then talk about the Philippines, not Italy or any other country.\n";            // Make the API request
            $response = Http::post("{$this->apiBaseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $fullPrompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 200,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($generatedText) {
                    // Format the response for better readability
                    $formattedResponse = $this->formatResponseText($generatedText);

                    // Cache the formatted response
                    Cache::put($cacheKey, $formattedResponse, (int) $this->cacheDuration * 60);
                    return $formattedResponse;
                }
            }

            Log::error("Gemini API error: {$response->status()} - {$response->body()}");
            return null;
        } catch (Exception $e) {
            Log::error("Gemini API exception: {$e->getMessage()}");
            return null;
        }
    }
}
