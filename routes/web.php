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
