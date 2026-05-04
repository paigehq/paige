<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Page Scout indexing', function () {
    it('includes a published page in search results', function () {
        $space = Space::factory()->create();
        Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'My Unique Giraffe Page',
        ]);

        $results = Page::search('Giraffe')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('My Unique Giraffe Page');
    });

    it('excludes a draft page from search results', function () {
        $space = Space::factory()->create();
        Page::factory()->for($space)->create([
            'status' => PageStatus::Draft,
            'title' => 'Hidden Draft Elephant Page',
        ]);

        $results = Page::search('Elephant')->get();

        expect($results)->toHaveCount(0);
    });

    it('excludes a soft-deleted page from search results', function () {
        $space = Space::factory()->create();
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Soon To Be Deleted Rhino Page',
        ]);

        $page->delete();

        $results = Page::search('Rhino')->get();

        expect($results)->toHaveCount(0);
    });

    it('includes space_slug in the searchable array', function () {
        $space = Space::factory()->create(['slug' => 'engineering']);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'title' => 'Test Panda Page',
        ]);

        $array = $page->load('space', 'tags')->toSearchableArray();

        expect($array['space_slug'])->toBe('engineering')
            ->and($array['space_id'])->toBe($space->id)
            ->and($array['status'])->toBe('published');
    });
});
