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

    it('returns 200 with correct props for a published page', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = Page::factory()
            ->for($space)
            ->published()
            ->create([
                'author_id' => $user->id,
                'last_editor_id' => $user->id,
                'parent_id' => null,
            ]);

        $this->get(route('pages.show', [$space, $page]))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('pages/Show')
                ->where('space.id', $space->id)
                ->where('page.id', $page->id)
                ->where('page.title', $page->title)
                ->where('page.slug', $page->slug)
                ->has('page.html')
                ->has('page.breadcrumb')
                ->has('page.children')
                ->has('page.updatedAt')
                ->has('tree')
            );
    });

    it('breadcrumb is ordered from root to current page', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $parent = Page::factory()
            ->for($space)
            ->published()
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id, 'parent_id' => null]);
        $child = Page::factory()
            ->for($space)
            ->published()
            ->childOf($parent)
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id]);

        $this->get(route('pages.show', [$space, $child]))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->has('page.breadcrumb', 2)
                ->where('page.breadcrumb.0.id', $parent->id)
                ->where('page.breadcrumb.1.id', $child->id)
            );
    });

    it('children list contains only direct published children ordered by position', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $parent = Page::factory()
            ->for($space)
            ->published()
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id, 'parent_id' => null]);
        $childB = Page::factory()
            ->for($space)
            ->published()
            ->childOf($parent)
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id, 'position' => 2]);
        $childA = Page::factory()
            ->for($space)
            ->published()
            ->childOf($parent)
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id, 'position' => 1]);
        Page::factory()
            ->for($space)
            ->draft()
            ->childOf($parent)
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id]);

        $this->get(route('pages.show', [$space, $parent]))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->has('page.children', 2)
                ->where('page.children.0.id', $childA->id)
                ->where('page.children.1.id', $childB->id)
            );
    });

    it('tree contains only published pages from this space', function () {
        $space = Space::factory()->create();
        $otherSpace = Space::factory()->create();
        $user = User::factory()->create();
        $page = Page::factory()
            ->for($space)
            ->published()
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id, 'parent_id' => null]);
        Page::factory()->for($otherSpace)->published()
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id, 'parent_id' => null]);
        Page::factory()->for($space)->draft()
            ->create(['author_id' => $user->id, 'last_editor_id' => $user->id, 'parent_id' => null]);

        $this->get(route('pages.show', [$space, $page]))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert->has('tree', 1));
    });

    it('returns 301 for a historical slug redirecting to current slug', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = Page::factory()
            ->for($space)
            ->published()
            ->create([
                'author_id' => $user->id,
                'last_editor_id' => $user->id,
                'slug' => 'current-slug',
            ]);
        PageSlugHistory::create(['page_id' => $page->id, 'slug' => 'old-slug']);

        $this->get("/s/{$space->slug}/old-slug")
            ->assertRedirect("/s/{$space->slug}/current-slug")
            ->assertStatus(301);
    });
});
