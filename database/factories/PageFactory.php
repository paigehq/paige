<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(4, false);
        $title = rtrim($title, '.');

        return [
            'space_id' => Space::factory(),
            'parent_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'This page explains '.lcfirst($title).'. It covers the core concepts and provides examples to help your team get started quickly.',
                            ],
                        ],
                    ],
                ],
            ]),
            'content_type' => 'tiptap',
            'status' => PageStatus::Draft,
            'author_id' => User::factory(),
            'last_editor_id' => null,
            'revision_number' => 0,
            'position' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Page $page) {
            if ($page->last_editor_id === null) {
                $page->last_editor_id = $page->author_id;
            }
        });
    }

    public function published(): static
    {
        return $this->state(['status' => PageStatus::Published]);
    }

    public function draft(): static
    {
        return $this->state(['status' => PageStatus::Draft]);
    }

    public function childOf(Page $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'space_id' => $parent->space_id,
        ]);
    }
}
