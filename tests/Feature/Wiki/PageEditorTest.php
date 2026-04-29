<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\SaveDraft;

describe('SaveDraft action', function () {
    it('updates title and content without creating a revision', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = Page::factory()
            ->for($space)
            ->for($user, 'author')
            ->for($user, 'lastEditor')
            ->create(['title' => 'Original', 'content' => null, 'status' => PageStatus::Draft]);

        $initialRevisionCount = $page->revisions()->count();

        app(SaveDraft::class)->handle($page, $user, 'Updated', '{"type":"doc","content":[]}');

        expect($page->fresh()->title)->toBe('Updated')
            ->and($page->fresh()->status)->toBe(PageStatus::Draft)
            ->and($page->revisions()->count())->toBe($initialRevisionCount);
    });
});

describe('PageController (editor)', function () {
    it('redirects unauthenticated user from GET /new', function () {
        $space = Space::factory()->create();
        $this->get(route('pages.create', $space))->assertRedirect(route('login'));
    });

    it('renders pages/Create for authenticated user', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $this->actingAs($user)
            ->get(route('pages.create', $space))
            ->assertInertia(fn ($a) => $a->component('pages/Create'));
    });

    it('creates a page and revision via POST', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();

        $this->actingAs($user)
            ->post(route('pages.store', $space), ['title' => 'New Page', 'content' => null])
            ->assertRedirect();

        $page = Page::where('title', 'New Page')->first();
        expect($page)->not->toBeNull()
            ->and($page->space_id)->toBe($space->id)
            ->and(PageRevision::where('page_id', $page->id)->count())->toBe(1);
    });

    it('unauthenticated user cannot POST store', function () {
        $space = Space::factory()->create();
        $this->post(route('pages.store', $space), ['title' => 'X'])
            ->assertRedirect(route('login'));
    });

    it('renders pages/Edit for authenticated user', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = Page::factory()
            ->for($space)->for($user, 'author')->for($user, 'lastEditor')
            ->create();

        $this->actingAs($user)
            ->get(route('pages.edit', [$space, $page]))
            ->assertInertia(fn ($a) => $a->component('pages/Edit'));
    });

    it('saves draft via PUT with action=draft', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = Page::factory()
            ->for($space)->for($user, 'author')->for($user, 'lastEditor')
            ->create(['status' => PageStatus::Draft]);

        $this->actingAs($user)->put(route('pages.update', [$space, $page]), [
            'title' => 'Draft Title',
            'content' => '{"type":"doc","content":[]}',
            'action' => 'draft',
        ])->assertRedirect();

        expect($page->fresh()->title)->toBe('Draft Title')
            ->and($page->fresh()->status)->toBe(PageStatus::Draft);
    });

    it('publishes via PUT with action=publish', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = Page::factory()
            ->for($space)->for($user, 'author')->for($user, 'lastEditor')
            ->create();

        $this->actingAs($user)->put(route('pages.update', [$space, $page]), [
            'title' => 'Published',
            'content' => '{"type":"doc","content":[]}',
            'action' => 'publish',
        ])->assertRedirect();

        expect($page->fresh()->status)->toBe(PageStatus::Published);
    });

    it('unauthenticated user cannot PUT update', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = Page::factory()
            ->for($space)->for($user, 'author')->for($user, 'lastEditor')
            ->create();

        $this->put(route('pages.update', [$space, $page]), [
            'title' => 'X',
            'content' => null,
            'action' => 'draft',
        ])->assertRedirect(route('login'));
    });

    it('soft-deletes page via DELETE', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = Page::factory()
            ->for($space)->for($user, 'author')->for($user, 'lastEditor')
            ->create();

        $this->actingAs($user)
            ->delete(route('pages.destroy', [$space, $page]))
            ->assertRedirect();

        expect(Page::withTrashed()->find($page->id)->deleted_at)->not->toBeNull();
    });
});
