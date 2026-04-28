<?php

use App\Enums\PageStatus;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\PublishPage;
use Illuminate\Support\Facades\Cache;

describe('PublishPage', function () {
    it('increments revision_number, sets status to published, and inserts a new revision row', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Getting Started');

        $newContent = json_encode(['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Updated']]],
        ]]);

        $published = app(PublishPage::class)->handle($page, $user, null, $newContent, 'First publish');

        expect($published->revision_number)->toBe(2)
            ->and($published->status)->toBe(PageStatus::Published)
            ->and($published->last_editor_id)->toBe($user->id);

        $this->assertDatabaseCount('page_revisions', 2);
        $this->assertDatabaseHas('page_revisions', [
            'page_id' => $page->id,
            'revision_number' => 2,
            'content' => $newContent,
            'change_summary' => 'First publish',
        ]);
    });

    it('deletes the Redis HTML cache key after committing', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'My Page');

        Cache::put("page:{$page->id}:html", '<p>stale cached html</p>');

        app(PublishPage::class)->handle($page, $user);

        expect(Cache::has("page:{$page->id}:html"))->toBeFalse();
    });

    it('updates the title when a new title is provided', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Old Title');

        $published = app(PublishPage::class)->handle($page, $user, 'New Title');

        expect($published->title)->toBe('New Title');
        $this->assertDatabaseHas('page_revisions', [
            'page_id' => $page->id,
            'title' => 'New Title',
            'revision_number' => 2,
        ]);
    });

    it('keeps the existing title and content when none are provided', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $content = json_encode(['type' => 'doc', 'content' => []]);
        $page = app(CreatePage::class)->handle($space, $user, 'Stable', $content);

        $published = app(PublishPage::class)->handle($page, $user);

        expect($published->title)->toBe('Stable')
            ->and($published->content)->toBe($content);
    });

    it('can be published multiple times, incrementing revision_number each time', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Iterative');

        app(PublishPage::class)->handle($page, $user); // revision 2
        $page->refresh();
        $final = app(PublishPage::class)->handle($page, $user); // revision 3

        expect($final->revision_number)->toBe(3);
        $this->assertDatabaseCount('page_revisions', 3);
    });
});
