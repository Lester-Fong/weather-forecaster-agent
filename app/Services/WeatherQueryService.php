<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Str;

class WeatherQueryService
{
    /**
     * The location service instance.
     *
     * @var \App\Services\LocationService
     */
    protected $locationService;

    /**
     * The weather service instance.
     *
     * @var \App\Services\WeatherService
     */
    protected $weatherService;

    /**
     * The Gemini service instance.
     *
     * @var \App\Services\GeminiService
     */
    protected $geminiService;

    /**
     * Create a new service instance.
     */
    public function __construct(
        LocationService $locationService,
        WeatherService $weatherService,
        GeminiService $geminiService
    ) {
        $this->locationService = $locationService;
        $this->weatherService = $weatherService;
        $this->geminiService = $geminiService;
    }

    /**
     * Process a weather query.
     *
     * @param string $query The user's query
     * @param Conversation $conversation The conversation object
     * @param int|null $locationId Optional specific location ID to use
     * @return array
     */
    public function processQuery(string $query, Conversation $conversation, ?int $locationId = null): array
    {
        // Extract date from query
        $dateInfo = $this->extractDate($query);

        // Extract query type (current, forecast, etc.)
        $queryType = $this->determineQueryType($query, $dateInfo);

        // Get location
        $location = null;

        // First priority: Use the provided location_id if available
        if ($locationId) {
            $location = \App\Models\Location::find($locationId);
            if ($location) {
                // Log that we're using the provided location_id
                \Illuminate\Support\Facades\Log::info("Using provided location_id: {$locationId} ({$location->name}, {$location->country})");
            }
        }

        // Second priority: Try to extract location from the query text
        if (!$location) {
            $locationInfo = $this->extractLocation($query);
            if ($locationInfo) {
                // Try to extract country from the location text
                $countryFilter = null;
                $locationText = $locationInfo['text'];

                // Check if the location text contains a comma followed by a country
                if (preg_match('/,\s*([A-Za-z\s]+)$/', $locationText, $matches)) {
                    $countryFilter = trim($matches[1]);
                    // Remove country from the search text to improve accuracy
                    $locationText = trim(str_replace($matches[0], '', $locationText));
                    \Illuminate\Support\Facades\Log::info("Extracted country '{$countryFilter}' from query");
                }

                // Search for locations with country filter if available
                $locationData = $this->locationService->searchLocations($locationText, 5, $countryFilter);
                if (! empty($locationData)) {
                    $locationData = $locationData[0]; // Take the first match
                    $location = $this->locationService->findOrCreateLocation(
                        $locationData['name'],
                        $locationData['country'],
                        $locationData['lat'],
                        $locationData['lon']
                    );
                    \Illuminate\Support\Facades\Log::info("Found location: {$location->name}, {$location->country}");
                }
            }
        }

        // Third priority: Try to get location from previous messages
        if (!$location) {
            $location = $this->getPreviousLocation($conversation);
        }

        // Process response based on query type and available data
        return $this->generateResponse($queryType, $location, $dateInfo, $query);
    }

    /**
     * Extract location from query.
     */
    protected function extractLocation(string $query): ?array
    {
        // Enhanced pattern matching for location with support for more complex sentences
        $patterns = [
            // Match "in/at/for/of [location]" patterns
            '/\b(?:in|at|for|of)\s+([A-Za-z\s\',]+?)(?:\s|$|\?|\.|,|;|tomorrow|today|next|this|on|the weather)/i',
            // Match "[location] weather/forecast" patterns
            '/\b([A-Za-z\s\',]+?)(?:\s+weather\b|\s+forecast\b)/i',
            // Match "weather in/at/for [location]" patterns
            '/\bweather\s+(?:in|at|for)\s+([A-Za-z\s\',]+)(?:\s|$|\?|\.)/i',
            // Match "planning to [verb] in [location]" patterns
            '/\bplanning to [a-z]+\s+in\s+([A-Za-z\s\',]+?)(?:\s|$|\?|\.|,|;|tomorrow|today)/i',
            // Match "going to [location]" patterns
            '/\bgoing to\s+([A-Za-z\s\',]+?)(?:\s|$|\?|\.|,|;|tomorrow|today)/i',
            // Match "visit/visiting [location]" patterns
            '/\b(?:visit|visiting)\s+([A-Za-z\s\',]+?)(?:\s|$|\?|\.|,|;|tomorrow|today)/i',
            // Match city names followed by "City" (e.g., "Batangas City", "New York City")
            '/\b([A-Za-z\s\']+\s+City)(?:\s|$|\?|\.|,|;|tomorrow|today)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $query, $matches)) {
                $location = trim($matches[1]);

                // Remove trailing commas, periods, and other punctuation
                $location = rtrim($location, ',;.!? ');

                // Filter out common words that aren't locations
                $commonWords = ['the', 'weather', 'forecast', 'current', 'today', 'tomorrow', 'week', 'weekend'];
                if (! in_array(strtolower($location), $commonWords) && strlen($location) > 2) {
                    return [
                        'text' => $location,
                        'pattern' => $pattern,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Extract date from query.
     */
    protected function extractDate(string $query): ?array
    {
        $now = Carbon::now();
        $lcQuery = strtolower($query);

        // Check for specific day references
        if (Str::contains($lcQuery, 'today')) {
            return [
                'date' => $now->copy(),
                'type' => 'specific',
                'text' => 'today',
            ];
        } elseif (Str::contains($lcQuery, 'tomorrow')) {
            return [
                'date' => $now->copy()->addDay(),
                'type' => 'specific',
                'text' => 'tomorrow',
            ];
        } elseif (Str::contains($lcQuery, ['day after tomorrow', 'after tomorrow'])) {
            return [
                'date' => $now->copy()->addDays(2),
                'type' => 'specific',
                'text' => 'day after tomorrow',
            ];
        } elseif (preg_match('/\b(this|next)\s+(week(?:end)?|monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $lcQuery, $matches)) {
            $dayOfWeek = strtolower($matches[2]);
            $prefix = strtolower($matches[1]);

            if ($dayOfWeek === 'weekend') {
                $targetDay = $prefix === 'this' ? 'Saturday' : 'next Saturday';
                $date = Carbon::parse($targetDay);

                return [
                    'date' => $date,
                    'type' => 'range',
                    'text' => $prefix . ' weekend',
                    'range_end' => $date->copy()->addDay(), // Sunday
                ];
            } elseif ($dayOfWeek === 'week') {
                $startDay = $prefix === 'this' ? 'Monday' : 'next Monday';
                $date = Carbon::parse($startDay);

                return [
                    'date' => $date,
                    'type' => 'range',
                    'text' => $prefix . ' week',
                    'range_end' => $date->copy()->addDays(6), // Sunday
                ];
            } else {
                // Specific day of week
                $day = ucfirst($dayOfWeek);
                $targetDay = $prefix === 'this' ? $day : 'next ' . $day;

                return [
                    'date' => Carbon::parse($targetDay),
                    'type' => 'specific',
                    'text' => $prefix . ' ' . $dayOfWeek,
                ];
            }
        }

        // Check for specific date patterns
        if (preg_match('/\b(\d{1,2})[\/\-\.](\d{1,2})(?:[\/\-\.](\d{2,4}))?\b/', $lcQuery, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = isset($matches[3]) ? (int) $matches[3] : $now->year;

            // Handle 2-digit year
            if ($year < 100) {
                $year += 2000;
            }

            try {
                return [
                    'date' => Carbon::createFromDate($year, $month, $day),
                    'type' => 'specific',
                    'text' => $matches[0],
                ];
            } catch (\Exception $e) {
                // Invalid date, ignore
            }
        }

        // Check for named dates
        $namedDates = [
            '/\b(?:on|for|this)\s+([A-Za-z]+\s+\d{1,2}(?:st|nd|rd|th)?(?:,\s+\d{4})?)\b/i',
            '/\b(\d{1,2}(?:st|nd|rd|th)?\s+(?:of\s+)?[A-Za-z]+(?:\s+\d{4})?)\b/i',
        ];

        foreach ($namedDates as $pattern) {
            if (preg_match($pattern, $lcQuery, $matches)) {
                try {
                    $dateText = $matches[1];
                    $date = Carbon::parse($dateText);

                    return [
                        'date' => $date,
                        'type' => 'specific',
                        'text' => $dateText,
                    ];
                } catch (\Exception $e) {
                    // Invalid date, continue to next pattern
                }
            }
        }

        // Default to current date if no date specified
        return [
            'date' => $now,
            'type' => 'default',
            'text' => 'now',
        ];
    }

    /**
     * Determine the type of weather query.
     */
    protected function determineQueryType(string $query, ?array $dateInfo): string
    {
        $lcQuery = strtolower($query);

        // Check if looking for a forecast
        if (Str::contains($lcQuery, ['forecast', 'week', 'next few days', '5 day', 'five day'])) {
            return 'forecast';
        }

        // Check if looking for temperature
        if (Str::contains($lcQuery, ['temperature', 'hot', 'cold', 'warm', 'cool', 'degrees'])) {
            return 'temperature';
        }

        // Check if looking for precipitation
        if (Str::contains($lcQuery, ['rain', 'snow', 'precipitation', 'raining', 'snowing', 'thunderstorm', 'storm'])) {
            return 'precipitation';
        }

        // Check if looking for wind
        if (Str::contains($lcQuery, ['wind', 'windy', 'breeze', 'gust'])) {
            return 'wind';
        }

        // Check if looking for humidity
        if (Str::contains($lcQuery, ['humid', 'humidity', 'moisture'])) {
            return 'humidity';
        }

        // Check if date is in the future
        if ($dateInfo && $dateInfo['type'] === 'specific' && $dateInfo['date']->isAfter(Carbon::today())) {
            return 'forecast';
        }

        // Default to current weather
        return 'current';
    }

    /**
     * Get location from previous messages in the conversation.
     */
    protected function getPreviousLocation(Conversation $conversation): ?Location
    {
        $message = $conversation->messages()
            ->whereNotNull('location_id')
            ->latest()
            ->first();

        return $message ? $message->location : null;
    }

    /**
     * Generate a response based on the query type, location, and date.
     */
    protected function generateResponse(string $queryType, ?Location $location, ?array $dateInfo, string $originalQuery): array
    {
        if (! $location) {
            return [
                'status' => 'error',
                'message' => "I couldn't determine the location you're asking about. Could you please specify a city or place? You can also allow location access in your browser for me to provide weather for your current location.",
                'location' => null,
                'data' => null,
                'query_type' => $queryType,
                'date_info' => $dateInfo,
            ];
        }

        $weatherData = null;
        $responseMessage = '';
        $formattedDateInfo = null;

        if ($dateInfo) {
            // Add formatted date for display
            $dateInfo['formatted'] = $dateInfo['date']->format('l, F j, Y');
            $formattedDateInfo = $dateInfo;
        }

        switch ($queryType) {
            case 'forecast':
                $weatherData = $this->weatherService->getForecast($location);
                break;

            case 'temperature':
            case 'precipitation':
            case 'wind':
            case 'humidity':
                if ($dateInfo['type'] === 'specific' && ! $dateInfo['date']->isToday()) {
                    $weatherData = $this->weatherService->getWeatherForDate($location, $dateInfo['date']);
                } else {
                    $weatherData = $this->weatherService->getCurrentWeather($location);
                }
                break;

            case 'current':
            default:
                $weatherData = $this->weatherService->getCurrentWeather($location);
                break;
        }

        // First try to generate a response using Gemini
        $geminiResponse = $this->geminiService->generateResponse(
            $originalQuery,
            [
                'location' => $location,
                'weather' => $weatherData,
                'date_info' => $formattedDateInfo,
                'query_type' => $queryType
            ]
        );

        // If Gemini generated a response, use it
        if ($geminiResponse) {
            $responseMessage = $geminiResponse;
        } else {
            // Fall back to our rule-based responses if Gemini fails
            switch ($queryType) {
                case 'forecast':
                    $responseMessage = $this->formatForecastResponse($weatherData, $location, $dateInfo);
                    break;

                case 'temperature':
                    if ($dateInfo['type'] === 'specific' && ! $dateInfo['date']->isToday()) {
                        $responseMessage = $this->formatTemperatureResponse($weatherData, $location, $dateInfo, false);
                    } else {
                        $responseMessage = $this->formatTemperatureResponse($weatherData, $location, $dateInfo, true);
                    }
                    break;

                case 'precipitation':
                    if ($dateInfo['type'] === 'specific' && ! $dateInfo['date']->isToday()) {
                        $responseMessage = $this->formatPrecipitationResponse($weatherData, $location, $dateInfo, false);
                    } else {
                        $responseMessage = $this->formatPrecipitationResponse($weatherData, $location, $dateInfo, true);
                    }
                    break;

                case 'wind':
                    if ($dateInfo['type'] === 'specific' && ! $dateInfo['date']->isToday()) {
                        $responseMessage = $this->formatWindResponse($weatherData, $location, $dateInfo, false);
                    } else {
                        $responseMessage = $this->formatWindResponse($weatherData, $location, $dateInfo, true);
                    }
                    break;

                case 'humidity':
                    if ($dateInfo['type'] === 'specific' && ! $dateInfo['date']->isToday()) {
                        $responseMessage = $this->formatHumidityResponse($weatherData, $location, $dateInfo, false);
                    } else {
                        $responseMessage = $this->formatHumidityResponse($weatherData, $location, $dateInfo, true);
                    }
                    break;

                case 'current':
                default:
                    $responseMessage = $this->formatCurrentWeatherResponse($weatherData, $location);
                    break;
            }
        }

        return [
            'status' => $weatherData ? 'success' : 'error',
            'message' => $weatherData ? $responseMessage : "Sorry, I couldn't retrieve the weather information at this time.",
            'location' => $location,
            'data' => $weatherData,
            'query_type' => $queryType,
            'date_info' => $dateInfo,
        ];
    }

    /**
     * Format a response for current weather.
     */
    protected function formatCurrentWeatherResponse(?array $weatherData, Location $location): string
    {
        if (! $weatherData) {
            return "Sorry, I couldn't get the current weather for {$location->name}.";
        }

        // Check if using OpenMeteo API (has 'current' key) or OpenWeatherMap API (has 'main' key)
        if (isset($weatherData['current'])) {
            // OpenMeteo API format
            $current = $weatherData['current'];
            $temp = round($current['temperature_2m']);
            $feelsLike = round($current['apparent_temperature']);
            $humidity = $current['relative_humidity_2m'];
            $windSpeed = round($current['wind_speed_10m'] * 3.6); // Convert to km/h if needed
            $weatherCode = $current['weather_code'];
            $description = $this->getWeatherConditionFromCode($weatherCode);

            return "Current weather in {$location->name}, {$location->country}: {$description}. " .
                "Temperature is {$temp}°C (feels like {$feelsLike}°C). " .
                "Humidity is {$humidity}% with wind speed of {$windSpeed} km/h.";
        } else if (isset($weatherData['main'])) {
            // OpenWeatherMap API format
            $temp = round($weatherData['main']['temp']);
            $feelsLike = round($weatherData['main']['feels_like']);
            $description = ucfirst($weatherData['weather'][0]['description']);
            $humidity = $weatherData['main']['humidity'];
            $windSpeed = round($weatherData['wind']['speed'] * 3.6); // Convert m/s to km/h

            return "Current weather in {$location->name}, {$location->country}: {$description}. " .
                "Temperature is {$temp}°C (feels like {$feelsLike}°C). " .
                "Humidity is {$humidity}% with wind speed of {$windSpeed} km/h.";
        } else {
            return "Sorry, I couldn't interpret the weather data for {$location->name}.";
        }
    }

    /**
     * Format a response for forecast.
     */
    protected function formatForecastResponse(?array $forecastData, Location $location, ?array $dateInfo): string
    {
        if (! $forecastData) {
            return "Sorry, I couldn't get the forecast for {$location->name}.";
        }

        // If it's a specific date in the forecast data
        if ($dateInfo && $dateInfo['type'] === 'specific') {
            // Check if using OpenMeteo API (has 'daily' key) or OpenWeatherMap API (has 'list' key)
            if (isset($forecastData['daily'])) {
                // OpenMeteo API format
                $dateFormatted = $dateInfo['date']->format('Y-m-d');
                $dateIndex = null;

                // Find the index for the requested date
                if (isset($forecastData['daily']['time']) && is_array($forecastData['daily']['time'])) {
                    foreach ($forecastData['daily']['time'] as $index => $dateStr) {
                        if ($dateStr === $dateFormatted) {
                            $dateIndex = $index;
                            break;
                        }
                    }
                }

                if ($dateIndex === null) {
                    return "Sorry, I don't have forecast data for {$dateInfo['text']} in {$location->name}.";
                }

                // Get temperature and weather condition for the day
                $minTemp = isset($forecastData['daily']['temperature_2m_min'][$dateIndex])
                    ? round($forecastData['daily']['temperature_2m_min'][$dateIndex]) : 'N/A';
                $maxTemp = isset($forecastData['daily']['temperature_2m_max'][$dateIndex])
                    ? round($forecastData['daily']['temperature_2m_max'][$dateIndex]) : 'N/A';

                // Determine weather condition based on available data
                $mainCondition = "varied";
                if (isset($forecastData['daily']['weathercode'][$dateIndex])) {
                    $mainCondition = $this->getWeatherConditionFromCode($forecastData['daily']['weathercode'][$dateIndex]);
                }

                $dateName = $dateInfo['date']->isToday() ? 'today' : ($dateInfo['date']->isTomorrow() ? 'tomorrow' : $dateInfo['date']->format('l, F j'));

                return "Weather forecast for {$dateName} in {$location->name}, {$location->country}: " .
                    "Expect {$mainCondition} conditions with temperatures between {$minTemp}°C and {$maxTemp}°C.";
            } else if (isset($forecastData['list'])) {
                // OpenWeatherMap API format
                $dateFormatted = $dateInfo['date']->format('Y-m-d');
                $dayForecasts = [];

                foreach ($forecastData['list'] as $item) {
                    $itemDate = Carbon::createFromTimestamp($item['dt'])->format('Y-m-d');
                    if ($itemDate === $dateFormatted) {
                        $dayForecasts[] = $item;
                    }
                }

                if (empty($dayForecasts)) {
                    return "Sorry, I don't have forecast data for {$dateInfo['text']} in {$location->name}.";
                }

                // Get min, max temps and overall weather for the day
                $minTemp = PHP_INT_MAX;
                $maxTemp = PHP_INT_MIN;
                $conditions = [];

                foreach ($dayForecasts as $forecast) {
                    $minTemp = min($minTemp, $forecast['main']['temp_min']);
                    $maxTemp = max($maxTemp, $forecast['main']['temp_max']);
                    $conditions[] = $forecast['weather'][0]['main'];
                }

                $minTemp = round($minTemp);
                $maxTemp = round($maxTemp);
                $mainCondition = $this->getMostCommonItem($conditions);

                $dateName = $dateInfo['date']->isToday() ? 'today' : ($dateInfo['date']->isTomorrow() ? 'tomorrow' : $dateInfo['date']->format('l, F j'));

                return "Weather forecast for {$dateName} in {$location->name}, {$location->country}: " .
                    "Expect {$mainCondition} conditions with temperatures between {$minTemp}°C and {$maxTemp}°C.";
            } else {
                return "Sorry, I don't have forecast data for {$dateInfo['text']} in {$location->name}.";
            }
        }

        // For general forecast (next few days)
        $forecastText = "5-day weather forecast for {$location->name}, {$location->country}:\n\n";

        // Check if using OpenMeteo API (has 'daily' key) or OpenWeatherMap API (has 'list' key)
        if (isset($forecastData['daily'])) {
            // OpenMeteo API format
            $days = min(5, count($forecastData['daily']['time'] ?? []));

            for ($i = 0; $i < $days; $i++) {
                $date = Carbon::parse($forecastData['daily']['time'][$i]);
                $minTemp = isset($forecastData['daily']['temperature_2m_min'][$i])
                    ? round($forecastData['daily']['temperature_2m_min'][$i]) : 'N/A';
                $maxTemp = isset($forecastData['daily']['temperature_2m_max'][$i])
                    ? round($forecastData['daily']['temperature_2m_max'][$i]) : 'N/A';
                $avgTemp = ($minTemp !== 'N/A' && $maxTemp !== 'N/A') ? round(($minTemp + $maxTemp) / 2) : 'N/A';

                $condition = isset($forecastData['daily']['weathercode'][$i])
                    ? $this->getWeatherConditionFromCode($forecastData['daily']['weathercode'][$i])
                    : "varied";

                $dayName = $date->isToday() ? 'Today' : ($date->isTomorrow() ? 'Tomorrow' : $date->format('l, M j'));

                $forecastText .= "{$dayName}: {$condition}, {$avgTemp}°C (min: {$minTemp}°C, max: {$maxTemp}°C)\n";
            }
        } else if (isset($forecastData['list'])) {
            // OpenWeatherMap API format
            $dayForecasts = [];
            foreach ($forecastData['list'] as $item) {
                $date = Carbon::createFromTimestamp($item['dt']);
                $day = $date->format('Y-m-d');

                if (! isset($dayForecasts[$day])) {
                    $dayForecasts[$day] = [
                        'temps' => [],
                        'conditions' => [],
                        'date' => $date,
                    ];
                }

                $dayForecasts[$day]['temps'][] = $item['main']['temp'];
                $dayForecasts[$day]['conditions'][] = $item['weather'][0]['main'];
            }

            // Only show 5 days
            $count = 0;
            foreach ($dayForecasts as $day => $data) {
                if ($count >= 5) {
                    break;
                }

                $avgTemp = round(array_sum($data['temps']) / count($data['temps']));
                $mainCondition = $this->getMostCommonItem($data['conditions']);
                $dayName = $data['date']->isToday() ? 'Today' : ($data['date']->isTomorrow() ? 'Tomorrow' : $data['date']->format('l, M j'));

                $forecastText .= "{$dayName}: {$mainCondition}, {$avgTemp}°C\n";
                $count++;
            }
        } else {
            return "Sorry, I couldn't get the forecast for {$location->name}.";
        }

        return $forecastText;
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
     * Format a response for temperature query.
     */
    protected function formatTemperatureResponse(?array $weatherData, Location $location, ?array $dateInfo, bool $isCurrent): string
    {
        if (! $weatherData) {
            return "Sorry, I couldn't get the temperature information for {$location->name}.";
        }

        if ($isCurrent) {
            $temp = round($weatherData['main']['temp']);
            $feelsLike = round($weatherData['main']['feels_like']);
            $minTemp = round($weatherData['main']['temp_min']);
            $maxTemp = round($weatherData['main']['temp_max']);

            return "Current temperature in {$location->name}, {$location->country} is {$temp}°C (feels like {$feelsLike}°C). " .
                "Today's range is from {$minTemp}°C to {$maxTemp}°C.";
        } else {
            // For future date
            $dateName = $dateInfo['date']->format('l, F j');

            // Handle different response formats based on whether it's a single timestamp or a daily forecast
            if (isset($weatherData['list'])) {
                // Calculate min, max, and average temperatures
                $temps = array_column(array_column($weatherData['list'], 'main'), 'temp');
                $avgTemp = round(array_sum($temps) / count($temps));
                $minTemp = round(min(array_column(array_column($weatherData['list'], 'main'), 'temp_min')));
                $maxTemp = round(max(array_column(array_column($weatherData['list'], 'main'), 'temp_max')));

                return "Temperature forecast for {$dateName} in {$location->name}, {$location->country}: " .
                    "Average temperature will be around {$avgTemp}°C with a range from {$minTemp}°C to {$maxTemp}°C.";
            } else {
                // Single timestamp (historical or current)
                $temp = round($weatherData['main']['temp']);

                return "Temperature for {$dateName} in {$location->name}, {$location->country} is {$temp}°C.";
            }
        }
    }

    /**
     * Format a response for precipitation query.
     */
    protected function formatPrecipitationResponse(?array $weatherData, Location $location, ?array $dateInfo, bool $isCurrent): string
    {
        if (! $weatherData) {
            return "Sorry, I couldn't get the precipitation information for {$location->name}.";
        }

        if ($isCurrent) {
            $weather = $weatherData['weather'][0];
            $precipitation = $this->getPrecipitationStatus($weather['main'], $weather['description']);
            $pop = isset($weatherData['pop']) ? round($weatherData['pop'] * 100) : 0;

            $response = "Currently in {$location->name}, {$location->country}: {$precipitation}.";
            if (isset($weatherData['rain']) && isset($weatherData['rain']['1h'])) {
                $rain = $weatherData['rain']['1h'];
                $response .= " Rainfall in the last hour: {$rain} mm.";
            }
            if (isset($weatherData['snow']) && isset($weatherData['snow']['1h'])) {
                $snow = $weatherData['snow']['1h'];
                $response .= " Snowfall in the last hour: {$snow} mm.";
            }

            return $response;
        } else {
            // For future date
            $dateName = $dateInfo['date']->format('l, F j');

            if (isset($weatherData['list'])) {
                // Calculate precipitation probability
                $conditions = [];
                $pops = [];
                $rain = 0;
                $snow = 0;

                foreach ($weatherData['list'] as $item) {
                    $conditions[] = $item['weather'][0]['main'];
                    if (isset($item['pop'])) {
                        $pops[] = $item['pop'];
                    }
                    if (isset($item['rain']) && isset($item['rain']['3h'])) {
                        $rain += $item['rain']['3h'];
                    }
                    if (isset($item['snow']) && isset($item['snow']['3h'])) {
                        $snow += $item['snow']['3h'];
                    }
                }

                $mainCondition = $this->getMostCommonItem($conditions);
                $precipStatus = $this->getPrecipitationStatus($mainCondition, '');
                $avgPop = ! empty($pops) ? round((array_sum($pops) / count($pops)) * 100) : 0;

                $response = "Precipitation forecast for {$dateName} in {$location->name}, {$location->country}: " .
                    "{$precipStatus} with {$avgPop}% chance of precipitation.";

                if ($rain > 0) {
                    $response .= ' Expected rainfall: ' . round($rain, 1) . ' mm.';
                }
                if ($snow > 0) {
                    $response .= ' Expected snowfall: ' . round($snow, 1) . ' mm.';
                }

                return $response;
            } else {
                // Single timestamp
                $weather = $weatherData['weather'][0];
                $precipitation = $this->getPrecipitationStatus($weather['main'], $weather['description']);

                return "Precipitation for {$dateName} in {$location->name}, {$location->country}: {$precipitation}.";
            }
        }
    }

    /**
     * Format a response for wind query.
     */
    protected function formatWindResponse(?array $weatherData, Location $location, ?array $dateInfo, bool $isCurrent): string
    {
        if (! $weatherData) {
            return "Sorry, I couldn't get the wind information for {$location->name}.";
        }

        if ($isCurrent) {
            $windSpeed = round($weatherData['wind']['speed'] * 3.6); // Convert m/s to km/h
            $windDirection = $this->getWindDirection($weatherData['wind']['deg']);
            $gust = isset($weatherData['wind']['gust']) ? round($weatherData['wind']['gust'] * 3.6) : null;

            $response = "Current wind in {$location->name}, {$location->country} is {$windSpeed} km/h from the {$windDirection}.";
            if ($gust) {
                $response .= " Gusts up to {$gust} km/h.";
            }

            return $response;
        } else {
            // For future date
            $dateName = $dateInfo['date']->format('l, F j');

            if (isset($weatherData['list'])) {
                // Calculate average wind speed and direction
                $speeds = [];
                $directions = [];
                $gusts = [];

                foreach ($weatherData['list'] as $item) {
                    $speeds[] = $item['wind']['speed'];
                    $directions[] = $item['wind']['deg'];
                    if (isset($item['wind']['gust'])) {
                        $gusts[] = $item['wind']['gust'];
                    }
                }

                $avgSpeed = round((array_sum($speeds) / count($speeds)) * 3.6); // Convert to km/h
                // Calculate the average direction (this is simplistic, not meteorologically accurate)
                $avgDirection = $this->getWindDirection(array_sum($directions) / count($directions));
                $maxGust = ! empty($gusts) ? round(max($gusts) * 3.6) : null;

                $response = "Wind forecast for {$dateName} in {$location->name}, {$location->country}: " .
                    "Average wind speed of {$avgSpeed} km/h from the {$avgDirection}.";

                if ($maxGust) {
                    $response .= " Gusts up to {$maxGust} km/h possible.";
                }

                return $response;
            } else {
                // Single timestamp
                $windSpeed = round($weatherData['wind']['speed'] * 3.6); // Convert m/s to km/h
                $windDirection = $this->getWindDirection($weatherData['wind']['deg']);

                return "Wind for {$dateName} in {$location->name}, {$location->country} is {$windSpeed} km/h from the {$windDirection}.";
            }
        }
    }

    /**
     * Format a response for humidity query.
     */
    protected function formatHumidityResponse(?array $weatherData, Location $location, ?array $dateInfo, bool $isCurrent): string
    {
        if (! $weatherData) {
            return "Sorry, I couldn't get the humidity information for {$location->name}.";
        }

        if ($isCurrent) {
            $humidity = $weatherData['main']['humidity'];

            return "Current humidity in {$location->name}, {$location->country} is {$humidity}%.";
        } else {
            // For future date
            $dateName = $dateInfo['date']->format('l, F j');

            if (isset($weatherData['list'])) {
                // Calculate average humidity
                $humidities = array_column(array_column($weatherData['list'], 'main'), 'humidity');
                $avgHumidity = round(array_sum($humidities) / count($humidities));

                return "Humidity forecast for {$dateName} in {$location->name}, {$location->country}: " .
                    "Average humidity will be around {$avgHumidity}%.";
            } else {
                // Single timestamp
                $humidity = $weatherData['main']['humidity'];

                return "Humidity for {$dateName} in {$location->name}, {$location->country} is {$humidity}%.";
            }
        }
    }

    /**
     * Get precipitation status from weather condition.
     */
    protected function getPrecipitationStatus(string $main, string $description): string
    {
        switch (strtolower($main)) {
            case 'rain':
            case 'drizzle':
                return 'Raining';
            case 'snow':
                return 'Snowing';
            case 'thunderstorm':
                return 'Thunderstorms';
            case 'clear':
                return 'No precipitation';
            case 'clouds':
                return 'Cloudy with no precipitation';
            case 'mist':
            case 'fog':
            case 'haze':
                return $main . ' with no precipitation';
            default:
                return $description ?: $main;
        }
    }

    /**
     * Get wind direction from degrees.
     */
    protected function getWindDirection(float $degrees): string
    {
        $directions = [
            'North',
            'North-Northeast',
            'Northeast',
            'East-Northeast',
            'East',
            'East-Southeast',
            'Southeast',
            'South-Southeast',
            'South',
            'South-Southwest',
            'Southwest',
            'West-Southwest',
            'West',
            'West-Northwest',
            'Northwest',
            'North-Northwest',
            'North',
        ];

        return $directions[round($degrees / 22.5) % 16];
    }

    /**
     * Get the most common item in an array.
     */
    protected function getMostCommonItem(array $array): ?string
    {
        if (empty($array)) {
            return null;
        }

        $counts = array_count_values($array);
        arsort($counts);

        return key($counts);
    }
}
