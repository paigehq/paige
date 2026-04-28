<?php

use App\Enums\PageStatus;
use App\Models\Page;
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
