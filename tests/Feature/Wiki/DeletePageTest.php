<?php

use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\DeletePage;

describe('DeletePage', function () {
    it('soft-deletes the page, leaving a deleted_at timestamp', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'To Delete');

        app(DeletePage::class)->handle($page);

        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    });

    it('does not delete children — they are orphaned with their original parent_id', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $parent = app(CreatePage::class)->handle($space, $user, 'Parent');
        $child = app(CreatePage::class)->handle($space, $user, 'Child', null, $parent);

        app(DeletePage::class)->handle($parent);

        // Parent is soft-deleted
        $this->assertSoftDeleted('pages', ['id' => $parent->id]);

        // Child still exists and keeps its parent_id
        $this->assertDatabaseHas('pages', [
            'id' => $child->id,
            'parent_id' => $parent->id,
            'deleted_at' => null,
        ]);
    });

    it('does not cascade to grandchildren either', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $parent = app(CreatePage::class)->handle($space, $user, 'Parent');
        $child = app(CreatePage::class)->handle($space, $user, 'Child', null, $parent);
        $grandchild = app(CreatePage::class)->handle($space, $user, 'Grandchild', null, $child);

        app(DeletePage::class)->handle($parent);

        $this->assertSoftDeleted('pages', ['id' => $parent->id]);
        expect(Page::find($child->id))->not->toBeNull()
            ->and(Page::find($grandchild->id))->not->toBeNull();
    });
});
