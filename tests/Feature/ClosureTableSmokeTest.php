<?php

use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('page ancestors', function () {
    it('resolves the correct ancestor chain for a 3-level nested page tree', function () {
        $user = User::factory()->create();
        $space = Space::factory()->for($user, 'owner')->create();

        $root = Page::factory()->create([
            'space_id' => $space->id,
            'parent_id' => null,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $child = Page::factory()->create([
            'space_id' => $space->id,
            'parent_id' => $root->id,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $grandchild = Page::factory()->create([
            'space_id' => $space->id,
            'parent_id' => $child->id,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        expect($root->ancestors()->count())->toBe(0)
            ->and($child->ancestors()->count())->toBe(1)
            ->and($grandchild->ancestors()->count())->toBe(2);

        // orderBy('depth') ascending: grandparent (depth=-2) → parent (depth=-1) → self (depth=0)
        // This is the natural breadcrumb order: root → child → current page
        $breadcrumb = $grandchild->ancestorsAndSelf()->orderBy('depth')->get();

        expect($breadcrumb)->toHaveCount(3)
            ->and($breadcrumb[0]->id)->toBe($root->id)
            ->and($breadcrumb[1]->id)->toBe($child->id)
            ->and($breadcrumb[2]->id)->toBe($grandchild->id);
    });

    it('has no ancestors for a root page', function () {
        $user = User::factory()->create();
        $space = Space::factory()->for($user, 'owner')->create();

        $page = Page::factory()->create([
            'space_id' => $space->id,
            'parent_id' => null,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        expect($page->ancestors()->count())->toBe(0)
            ->and($page->ancestorsAndSelf()->count())->toBe(1)
            ->and($page->ancestorsAndSelf()->first()->id)->toBe($page->id);
    });
});
