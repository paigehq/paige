<?php

use App\Models\Page;
use App\Models\Space;
use App\Models\User;

describe('SpaceController::show', function () {
    it('returns 404 for an unknown space slug', function () {
        $this->get(route('spaces.show', 'nonexistent'))
            ->assertNotFound();
    });

    it('returns 200 with null page when space has no published pages', function () {
        $space = Space::factory()->create();

        $this->get(route('spaces.show', $space))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('spaces/Show')
                ->where('space.id', $space->id)
                ->where('space.name', $space->name)
                ->where('page', null)
                ->has('tree')
            );
    });

    it('returns first published root page as page data', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = Page::factory()
            ->for($space)
            ->published()
            ->create([
                'author_id' => $user->id,
                'last_editor_id' => $user->id,
                'parent_id' => null,
                'position' => 1,
            ]);

        $this->get(route('spaces.show', $space))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert
                ->component('spaces/Show')
                ->where('page.id', $page->id)
                ->where('page.slug', $page->slug)
                ->where('page.title', $page->title)
                ->has('page.html')
                ->has('page.breadcrumb')
                ->has('page.children')
                ->has('page.updatedAt')
            );
    });

    it('ignores draft pages and returns null page when only drafts exist', function () {
        $space = Space::factory()->create();
        Page::factory()->for($space)->draft()->create(['parent_id' => null]);

        $this->get(route('spaces.show', $space))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert->where('page', null));
    });
});
