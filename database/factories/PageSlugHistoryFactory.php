<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageSlugHistory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PageSlugHistory>
 */
class PageSlugHistoryFactory extends Factory
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
            'slug' => Str::slug($this->faker->unique()->words(3, true)),
            'created_at' => now(),
        ];
    }
}
