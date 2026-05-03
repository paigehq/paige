<?php

use App\Models\Tag;

describe('GET /api/tags', function () {
    it('returns matching tags for a valid query', function () {
        Tag::factory()->create(['name' => 'laravel', 'slug' => 'laravel']);
        Tag::factory()->create(['name' => 'lara-vue', 'slug' => 'lara-vue']);
        Tag::factory()->create(['name' => 'php', 'slug' => 'php']);

        $response = $this->getJson('/api/tags?q=lara');

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'laravel']);
        $response->assertJsonFragment(['name' => 'lara-vue']);
        $response->assertJsonMissing(['name' => 'php']);
    });

    it('returns up to 10 results', function () {
        Tag::factory()->count(15)->sequence(
            fn ($seq) => ['name' => "tag-$seq->index", 'slug' => "tag-$seq->index"]
        )->create();

        $response = $this->getJson('/api/tags?q=tag');

        $response->assertOk();
        $response->assertJsonCount(10);
    });

    it('returns 422 when q is empty', function () {
        $response = $this->getJson('/api/tags?q=');

        $response->assertUnprocessable();
    });

    it('returns 422 when q is absent', function () {
        $response = $this->getJson('/api/tags');

        $response->assertUnprocessable();
    });

    it('requires no authentication', function () {
        Tag::factory()->create(['name' => 'public-tag', 'slug' => 'public-tag']);

        $response = $this->getJson('/api/tags?q=public');

        $response->assertOk();
    });
});
