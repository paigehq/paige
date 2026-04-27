<?php

namespace Database\Factories;

use App\Enums\PermissionAction;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => Space::factory(),
            'action' => $this->faker->randomElement(PermissionAction::cases()),
            'granted' => true,
        ];
    }
}
