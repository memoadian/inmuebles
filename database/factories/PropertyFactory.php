<?php

namespace Database\Factories;

use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->streetName().' '.$this->faker->buildingNumber();

        return [
            'user_id' => User::factory(),
            'property_type_id' => PropertyType::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => $this->faker->paragraph(),
            'operation' => $this->faker->randomElement(['sale', 'rent']),
            'price' => $this->faker->numberBetween(500_000, 12_000_000),
            'currency' => 'MXN',
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 4),
            'parking_spaces' => $this->faker->numberBetween(0, 3),
            'built_area' => $this->faker->numberBetween(60, 400),
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
