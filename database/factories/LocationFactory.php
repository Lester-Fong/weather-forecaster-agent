<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'country' => $this->faker->country(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'timezone' => $this->faker->timezone(),
            'population' => $this->faker->numberBetween(1000, 1000000),
            'elevation' => $this->faker->numberBetween(0, 1000),
            'feature_code' => 'PPLA',
            'country_code' => $this->faker->countryCode(),
            'admin1' => $this->faker->state(),
            'admin2' => $this->faker->city(),
            'admin3' => null,
            'admin4' => null,
            'search_count' => $this->faker->numberBetween(0, 100),
            'last_searched_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
