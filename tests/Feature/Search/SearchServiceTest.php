<?php

use App\Enums\PageStatus;
use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Search\SearchService;
use App\Space\SpaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SearchService', function () {
    it('returns a published page in a public space for an unauthenticated user', function () {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Visible Flamingo Page',
        ]);

        $results = app(SearchService::class)->search('Flamingo');

        expect($results)->toHaveCount(1)
            ->and($results[0]['title'])->toBe('Visible Flamingo Page');
    });

    it('hides a published page in a private space from an unauthenticated user', function () {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Hidden Flamingo Page',
        ]);

        $results = app(SearchService::class)->search('Flamingo');

        expect($results)->toHaveCount(0);
    });

    it('returns a private space page to an authenticated user with read permission', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Private Toucan Page',
        ]);

        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Read,
            'granted' => true,
        ]);

        $results = app(SearchService::class)->search('Toucan', $user);

        expect($results)->toHaveCount(1);
    });

    it('hides a private space page from an authenticated user without read permission', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Secret Toucan Page',
        ]);

        $results = app(SearchService::class)->search('Toucan', $user);

        expect($results)->toHaveCount(0);
    });

    it('excludes a draft page even for a user with write permission', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Draft,
            'title' => 'Draft Pelican Page',
        ]);

        $results = app(SearchService::class)->search('Pelican', $user);

        expect($results)->toHaveCount(0);
    });

    it('returns result shape with expected keys', function () {
        $space = Space::factory()->create([
            'name' => 'Engineering',
            'slug' => 'engineering',
            'visibility' => SpaceVisibility::Public,
        ]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Shape Heron Page',
            'slug' => 'shape-heron-page',
        ]);

        $results = app(SearchService::class)->search('Heron');

        expect($results[0])->toHaveKeys(['title', 'excerpt', 'spaceName', 'spaceSlug', 'pageSlug', 'updatedAt'])
            ->and($results[0]['spaceName'])->toBe('Engineering')
            ->and($results[0]['spaceSlug'])->toBe('engineering')
            ->and($results[0]['pageSlug'])->toBe('shape-heron-page');
    });

    it('returns empty array when query is empty', function () {
        $results = app(SearchService::class)->search('');

        expect($results)->toBe([]);
    });

    it('highlights query terms in excerpt with mark tags', function () {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $tiptapJson = json_encode([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'This page is about penguins in Antarctica.']],
                ],
            ],
        ]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Penguin Research',
            'content' => $tiptapJson,
        ]);

        $results = app(SearchService::class)->search('penguins');

        expect($results[0]['excerpt'])->toContain('<mark>');
    });
});

describe('SearchService — space archiving', function () {
    it('excludes pages from an archived space', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Archived Space Condor Page',
        ]);

        // Verify page is findable before archiving
        $before = app(SearchService::class)->search('Condor', $user);
        expect($before)->toHaveCount(1);

        // Archive the space
        app(SpaceService::class)->archive($space);

        // Page should no longer appear in search results
        $after = app(SearchService::class)->search('Condor', $user);
        expect($after)->toHaveCount(0);
    });
});
