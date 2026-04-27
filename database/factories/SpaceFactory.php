<?php

namespace Database\Factories;

use App\Enums\SpaceVisibility;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'visibility' => SpaceVisibility::Private,
            'owner_id' => User::factory(),
            'settings' => null,
        ];
    }

    public function public(): static
    {
        return $this->state(['visibility' => SpaceVisibility::Public]);
    }

    public function secret(): static
    {
        return $this->state(['visibility' => SpaceVisibility::Secret]);
    }
}
