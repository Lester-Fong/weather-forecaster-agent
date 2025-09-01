<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\WeatherCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeatherCacheFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WeatherCache::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'type' => $this->faker->randomElement(['current', 'forecast', 'hourly']),
            'date' => $this->faker->date(),
            'data' => [
                'temperature' => $this->faker->randomFloat(1, -10, 40),
                'weather_code' => $this->faker->numberBetween(0, 99),
                'wind_speed' => $this->faker->randomFloat(1, 0, 30),
                'time' => Carbon::now()->format('Y-m-d\TH:i'),
            ],
            'expires_at' => Carbon::now()->addMinutes($this->faker->numberBetween(10, 60)),
        ];
    }

    /**
     * Indicate that the model's cache is expired.
     */
    public function expired(): self
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => Carbon::now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate that the model's cache is for current weather.
     */
    public function current(): self
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'current',
        ]);
    }

    /**
     * Indicate that the model's cache is for forecast weather.
     */
    public function forecast(): self
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'forecast',
            'data' => [
                'date' => Carbon::now()->format('Y-m-d'),
                'max_temp' => $this->faker->randomFloat(1, 0, 40),
                'min_temp' => $this->faker->randomFloat(1, -10, 30),
                'weather_code' => $this->faker->numberBetween(0, 99),
                'precipitation_probability' => $this->faker->numberBetween(0, 100),
            ],
        ]);
    }
}
