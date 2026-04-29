<?php

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use App\Wiki\Actions\CreateRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class)->use(RefreshDatabase::class);

describe('CreateRevision', function () {
    it('inserts a new revision row on every call and never updates an existing one', function () {
        $page = Page::factory()->create(['revision_number' => 1]);
        $editor = User::factory()->create();
        $action = app(CreateRevision::class);

        DB::transaction(fn () => $action->handle($page, $editor, 'Initial save'));

        $page->increment('revision_number');

        DB::transaction(fn () => $action->handle($page, $editor, 'Second save'));

        expect(PageRevision::count())->toBe(2)
            ->and(PageRevision::where('revision_number', 1)->count())->toBe(1)
            ->and(PageRevision::where('revision_number', 2)->count())->toBe(1);
    });

    it('stores the new content that is currently on the page model', function () {
        $content = json_encode(['type' => 'doc', 'content' => []]);
        $page = Page::factory()->create(['revision_number' => 1, 'content' => $content, 'title' => 'Hello']);
        $editor = User::factory()->create();

        DB::transaction(fn () => app(CreateRevision::class)->handle($page, $editor));

        $revision = PageRevision::first();
        expect($revision->title)->toBe('Hello')
            ->and($revision->content)->toBe($content)
            ->and($revision->revision_number)->toBe(1)
            ->and($revision->editor_id)->toBe($editor->id);
    });
});
