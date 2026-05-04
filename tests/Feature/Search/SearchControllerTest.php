<?php

use App\Enums\PageStatus;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /search', function () {
    it('renders the Search Inertia page', function () {
        $this->get('/search')
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('Search')
                ->has('query')
                ->has('results')
            );
    });

    it('returns empty results for a blank query', function () {
        $this->get('/search?q=')
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('Search')
                ->where('results', [])
            );
    });

    it('returns matching published pages for an authenticated user', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Authenticated Wombat Search',
        ]);

        $this->actingAs($user)
            ->get('/search?q=Wombat')
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('Search')
                ->where('query', 'Wombat')
                ->has('results', 1)
                ->where('results.0.title', 'Authenticated Wombat Search')
            );
    });

    it('returns public space results to unauthenticated users', function () {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Public Wombat Search',
        ]);

        $this->get('/search?q=Wombat')
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->has('results', 1)
            );
    });

    it('hides private space results from unauthenticated users', function () {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Private Wombat Search',
        ]);

        $this->get('/search?q=Wombat')
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->where('results', [])
            );
    });
});
