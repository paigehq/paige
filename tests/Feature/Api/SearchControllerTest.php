<?php

use App\Enums\PageStatus;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Search\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('api.search');
});

describe('GET /api/search', function () {
    it('returns results for an authenticated user', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Deployment Strategies',
        ]);

        $this->actingAs($user)
            ->getJson('/api/search?q=deployment')
            ->assertOk()
            ->assertJsonStructure([
                'results' => [['id', 'title', 'excerpt', 'space_name', 'space_slug', 'page_url', 'updated_at']],
                'total',
                'query',
            ])
            ->assertJsonPath('query', 'deployment')
            ->assertJsonPath('results.0.title', 'Deployment Strategies');
    });

    it('excludes pages from spaces the user cannot read', function () {
        $user = User::factory()->create();
        $privateSpace = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        Page::factory()->for($privateSpace)->create([
            'status' => PageStatus::Published,
            'title' => 'Secret Space Content',
        ]);

        $this->actingAs($user)
            ->getJson('/api/search?q=secret-space-content')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'results');
    });

    it('returns public results for unauthenticated requests without requiring auth', function () {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Public Knowledge Base',
        ]);

        $this->getJson('/api/search?q=public')
            ->assertOk()
            ->assertJsonPath('total', 1);
    });

    it('caps results at 25 when limit exceeds the maximum', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->count(30)->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Capybara Page',
        ]);

        $this->actingAs($user)
            ->getJson('/api/search?q=capybara&limit=30')
            ->assertOk()
            ->assertJsonCount(25, 'results');
    });

    it('returns 429 with Retry-After header when rate limit is exceeded', function () {
        // Guests get 10 req/min; exhaust the limit, then assert 429
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/search?q=test');
        }

        $this->getJson('/api/search?q=test')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    });

    it('returns 503 with a JSON error body when Meilisearch is unavailable', function () {
        $this->mock(SearchService::class, function ($mock) {
            $mock->shouldReceive('searchForApi')
                ->andThrow(new RuntimeException('Connection refused'));
        });

        $this->getJson('/api/search?q=anything')
            ->assertStatus(503)
            ->assertJson(['error' => 'Search service temporarily unavailable. Please try again shortly.']);
    });
});
