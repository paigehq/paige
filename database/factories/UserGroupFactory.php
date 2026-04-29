<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserGroup>
 */
class UserGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true).' team';

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'space_id' => Space::factory(),
        ];
    }

    public function global(): static
    {
        return $this->state(['space_id' => null]);
    }
}
