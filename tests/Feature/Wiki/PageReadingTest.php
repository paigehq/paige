<?php

use App\Models\Page;
use App\Models\PageSlugHistory;
use App\Models\Space;
use App\Models\User;

describe('PageController::show', function () {
    it('returns 404 for an unknown page slug in a valid space', function () {
        $space = Space::factory()->create();

        $this->get("/s/{$space->slug}/nonexistent")
            ->assertNotFound();
    });

    it('returns 404 for a page slug from a different space', function () {
        $space = Space::factory()->create();
        $otherSpace = Space::factory()->create();
        $page = Page::factory()->for($otherSpace)->published()->create();

        $this->get("/s/{$space->slug}/{$page->slug}")
            ->assertNotFound();
    });

    it('returns 404 for a draft page', function () {
        $space = Space::factory()->create();
        $page = Page::factory()->for($space)->draft()->create();

        $this->get(route('pages.show', [$space, $page]))
            ->assertNotFound();
    });
});
