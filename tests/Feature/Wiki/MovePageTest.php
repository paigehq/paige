<?php

use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\MovePage;
use App\Wiki\Exceptions\CircularReferenceException;

describe('MovePage', function () {
    it('updates parent_id and the ancestors relationship reflects the new tree position', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();

        // root → child → grandchild
        $root = app(CreatePage::class)->handle($space, $user, 'Root');
        $child = app(CreatePage::class)->handle($space, $user, 'Child', null, $root);
        $grandchild = app(CreatePage::class)->handle($space, $user, 'Grandchild', null, $child);

        // Create a second branch: root → sibling
        $sibling = app(CreatePage::class)->handle($space, $user, 'Sibling', null, $root);

        // Move grandchild under sibling
        $moved = app(MovePage::class)->handle($grandchild, $sibling);

        expect($moved->parent_id)->toBe($sibling->id);

        // Verify ancestors: sibling → root
        $ancestorIds = $moved->ancestors()->pluck('id')->all();
        expect($ancestorIds)->toContain($sibling->id)
            ->and($ancestorIds)->toContain($root->id)
            ->and($ancestorIds)->not->toContain($child->id);
    });

    it('allows moving a page to the root level (null parent)', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();

        $root = app(CreatePage::class)->handle($space, $user, 'Root');
        $child = app(CreatePage::class)->handle($space, $user, 'Child', null, $root);

        $moved = app(MovePage::class)->handle($child, null);

        expect($moved->parent_id)->toBeNull();
    });

    it('throws CircularReferenceException when moving a page to itself', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Page');

        expect(fn () => app(MovePage::class)->handle($page, $page))
            ->toThrow(CircularReferenceException::class);
    });

    it('throws CircularReferenceException when moving a page to a direct child', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();

        $parent = app(CreatePage::class)->handle($space, $user, 'Parent');
        $child = app(CreatePage::class)->handle($space, $user, 'Child', null, $parent);

        expect(fn () => app(MovePage::class)->handle($parent, $child))
            ->toThrow(CircularReferenceException::class);
    });

    it('throws CircularReferenceException when moving to a deep descendant and leaves parent_id unchanged',
        function () {
            $space = Space::factory()->create();
            $user = User::factory()->create();
            $root = app(CreatePage::class)->handle($space, $user, 'Root');
            $child = app(CreatePage::class)->handle($space, $user, 'Child', null, $root);
            $grandchild = app(CreatePage::class)->handle($space, $user, 'Grandchild', null, $child);
            $great = app(CreatePage::class)->handle($space, $user, 'Great', null, $grandchild);

            expect(fn () => app(MovePage::class)->handle($root, $great))
                ->toThrow(CircularReferenceException::class);

            // Tree must be unmodified
            expect($root->fresh()->parent_id)->toBeNull();
        });
});
