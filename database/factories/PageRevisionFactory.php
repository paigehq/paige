<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageRevision>
 */
class PageRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'title' => $this->faker->sentence(4, false),
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Updated content: '.$this->faker->paragraph(),
                            ],
                        ],
                    ],
                ],
            ]),
            'editor_id' => User::factory(),
            'revision_number' => $this->faker->numberBetween(1, 50),
            'change_summary' => $this->faker->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}
