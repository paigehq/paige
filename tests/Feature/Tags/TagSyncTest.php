<?php

use App\Models\Page;
use App\Models\Space;
use App\Models\Tag;
use App\Models\User;
use App\Wiki\Actions\PublishPage;
use App\Wiki\Actions\SaveDraft;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\Jobs\MakeSearchable;

describe('tag sync on page save', function () {
    it('creates a new tag implicitly and associates it on publish', function () {
        $space = Space::factory()->create();
        $page = Page::factory()->for($space)->create();
        $user = User::factory()->create();

        app(PublishPage::class)->handle($page, $user, tagNames: ['laravel']);

        expect(Tag::where('slug', 'laravel')->exists())->toBeTrue()
            ->and($page->tags()->where('slug', 'laravel')->exists())->toBeTrue();
    });

    it('uses an existing tag and does not create a duplicate', function () {
        $space = Space::factory()->create();
        $page = Page::factory()->for($space)->create();
        $user = User::factory()->create();
        $existing = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

        app(PublishPage::class)->handle($page, $user, tagNames: ['PHP']);

        expect(Tag::where('slug', 'php')->count())->toBe(1)
            ->and($page->tags()->first()->id)->toBe($existing->id);
    });

    it('removes a tag from a page but retains the tag record', function () {
        $space = Space::factory()->create();
        $page = Page::factory()->for($space)->create();
        $user = User::factory()->create();
        Tag::factory()->create(['name' => 'vue', 'slug' => 'vue']);

        app(PublishPage::class)->handle($page, $user, tagNames: ['vue']);
        expect($page->tags()->count())->toBe(1);

        // Remove tag by syncing with empty array
        app(PublishPage::class)->handle($page, $user, tagNames: []);

        expect($page->fresh()->tags()->count())->toBe(0)
            ->and(Tag::where('slug', 'vue')->exists())->toBeTrue(); // tag record kept
    });

    it('syncs tags on draft save', function () {
        $space = Space::factory()->create();
        $page = Page::factory()->for($space)->create();
        $user = User::factory()->create();

        app(SaveDraft::class)->handle($page, $user, null, null, tagNames: ['draft-tag']);

        expect($page->tags()->where('slug', 'draft-tag')->exists())->toBeTrue();
    });

    it('queues a scout re-index when tags change on publish', function () {
        config(['scout.queue' => true]);
        Queue::fake();

        $space = Space::factory()->create();
        $page = Page::factory()->for($space)->create();
        $user = User::factory()->create();

        app(PublishPage::class)->handle($page, $user, tagNames: ['test-tag']);

        Queue::assertPushed(MakeSearchable::class);
    });
});
