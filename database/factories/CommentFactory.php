<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
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
            'author_id' => User::factory(),
            'body' => $this->faker->paragraph(),
            'parent_id' => null,
        ];
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state([
            'page_id' => $parent->page_id,
            'parent_id' => $parent->id,
        ]);
    }
}
