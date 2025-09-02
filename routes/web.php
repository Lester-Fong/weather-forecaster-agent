<?php

use App\Http\Controllers\WeatherAgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Weather Agent API
Route::post('/api/weather/query', [WeatherAgentController::class, 'query']);
Route::get('/api/weather/conversation', [WeatherAgentController::class, 'getConversation']);
Route::post('/api/weather/detect-location', [WeatherAgentController::class, 'detectLocation']);

// Debug route
Route::get('/debug-api-key', function () {
    // Allow access in any environment for testing
    $key = config('services.gemini.key');
    $env_key = env('GEMINI_API_KEY');

    return response()->json([
        'app_env' => app()->environment(),
        'gemini_key_config' => [
            'key_exists' => !empty($key),
            'key_length' => strlen($key),
            'key_first_chars' => !empty($key) ? substr($key, 0, 5) . '...' : 'n/a',
        ],
        'gemini_key_env' => [
            'key_exists' => !empty($env_key),
            'key_length' => strlen($env_key),
            'key_first_chars' => !empty($env_key) ? substr($env_key, 0, 5) . '...' : 'n/a',
        ],
        'env_variables' => [
            'APP_ENV' => env('APP_ENV'),
            'APP_DEBUG' => env('APP_DEBUG'),
            'LOG_LEVEL' => env('LOG_LEVEL'),
        ],
        'server_vars' => [
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        ],
    ]);
});

// Test Gemini API
Route::get('/test-gemini', function () {
    $apiKey = config('services.gemini.key');
    $apiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    $model = 'gemini-2.0-flash';

    try {
        $response = Http::post("{$apiBaseUrl}/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'Say hello and tell me what the weather is like in a short sentence.'
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 800,
            ]
        ]);

        $data = $response->json();
        \Illuminate\Support\Facades\Log::info('Gemini API Test Response', $data);

        return response()->json([
            'success' => $response->successful(),
            'status' => $response->status(),
            'response' => $data,
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Gemini API Test Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Debug all environment variables (redacted for security)
Route::get('/debug-env', function () {
    $env = [];

    foreach ($_ENV as $key => $value) {
        // Redact sensitive values
        if (preg_match('/(key|token|secret|password)/i', $key)) {
            $env[$key] = '[REDACTED]';
        } else {
            $env[$key] = $value;
        }
    }

    // Check if Gemini key is set in different ways
    $env['GEMINI_KEY_ENV_EXISTS'] = isset($_ENV['GEMINI_API_KEY']);
    $env['GEMINI_KEY_SERVER_EXISTS'] = isset($_SERVER['GEMINI_API_KEY']);
    $env['GEMINI_KEY_GETENV'] = !empty(getenv('GEMINI_API_KEY')) ? 'EXISTS' : 'NOT_FOUND';
    $env['GEMINI_KEY_ENV_HELPER'] = !empty(env('GEMINI_API_KEY')) ? 'EXISTS' : 'NOT_FOUND';
    $env['GEMINI_KEY_CONFIG'] = !empty(config('services.gemini.key')) ? 'EXISTS' : 'NOT_FOUND';

    return response()->json([
        'environment_variables' => $env,
        'server_variables' => [
            'PHP_VERSION' => PHP_VERSION,
            'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        ]
    ]);
});
