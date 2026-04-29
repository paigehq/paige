<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Exceptions\SlugExhaustedException;

describe('CreatePage', function () {
    it('creates a page as draft with revision_number 1 and inserts a revision row', function () {
        $space = Space::factory()->create();
        $author = User::factory()->create();
        $content = json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello world']]],
            ],
        ]);

        $page = app(CreatePage::class)->handle($space, $author, 'Getting Started', $content);

        expect($page->status)->toBe(PageStatus::Draft)
            ->and($page->revision_number)->toBe(1)
            ->and($page->slug)->toBe('getting-started')
            ->and($page->author_id)->toBe($author->id)
            ->and($page->last_editor_id)->toBe($author->id)
            ->and($page->space_id)->toBe($space->id);

        $this->assertDatabaseHas('page_revisions', [
            'page_id' => $page->id,
            'revision_number' => 1,
            'title' => 'Getting Started',
            'content' => $content,
            'editor_id' => $author->id,
        ]);
    });

    it('assigns a parent when one is provided', function () {
        $space = Space::factory()->create();
        $author = User::factory()->create();
        $parent = Page::factory()->create(['space_id' => $space->id]);

        $child = app(CreatePage::class)->handle($space, $author, 'Child Page', null, $parent);

        expect($child->parent_id)->toBe($parent->id);
    });

    it('appends -2 suffix when the base slug already exists in the space', function () {
        $space = Space::factory()->create();
        $author = User::factory()->create();

        $first = app(CreatePage::class)->handle($space, $author, 'Getting Started');
        $second = app(CreatePage::class)->handle($space, $author, 'Getting Started');

        expect($first->slug)->toBe('getting-started')
            ->and($second->slug)->toBe('getting-started-2');
    });

    it('keeps incrementing the suffix past -2 when multiple collisions exist', function () {
        $space = Space::factory()->create();
        $author = User::factory()->create();

        app(CreatePage::class)->handle($space, $author, 'Getting Started');          // getting-started
        app(CreatePage::class)->handle($space, $author, 'Getting Started');          // getting-started-2
        $third = app(CreatePage::class)->handle($space, $author, 'Getting Started'); // getting-started-3

        expect($third->slug)->toBe('getting-started-3');
    });

    it('does not reuse a slug from a soft-deleted page', function () {
        $space = Space::factory()->create();
        $author = User::factory()->create();

        $existing = Page::factory()->create(['space_id' => $space->id, 'slug' => 'getting-started']);
        $existing->delete(); // soft-delete

        $page = app(CreatePage::class)->handle($space, $author, 'Getting Started');

        expect($page->slug)->toBe('getting-started-2');
    });

    it('throws SlugExhaustedException when the base slug and all -2…-10 suffixes are taken', function () {
        $space = Space::factory()->create();
        $author = User::factory()->create();

        // Occupy the base slug
        app(CreatePage::class)->handle($space, $author, 'Getting Started');

        // Occupy -2 through -10 directly via factory (no revision overhead)
        for ($i = 2; $i <= 10; $i++) {
            Page::factory()->create(['space_id' => $space->id, 'slug' => "getting-started-{$i}"]);
        }

        expect(fn () => app(CreatePage::class)->handle($space, $author, 'Getting Started'))
            ->toThrow(SlugExhaustedException::class);
    });

    it('stores the change_summary on the revision row when provided', function () {
        $space = Space::factory()->create();
        $author = User::factory()->create();

        $page = app(CreatePage::class)->handle($space, $author, 'Page', null, null, 'Initial draft');

        $this->assertDatabaseHas('page_revisions', [
            'page_id' => $page->id,
            'change_summary' => 'Initial draft',
        ]);
    });
});
