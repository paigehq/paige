<?php

use App\Models\PageRevision;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\PublishPage;
use App\Wiki\RevisionService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

describe('RevisionService', function () {
    it('returns revisions newest first', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'First', '{"type":"doc","content":[]}');

        app(PublishPage::class)->handle($page->fresh(), $user, 'v2', '{"type":"doc","content":[]}');

        $revisions = app(RevisionService::class)->getRevisions($page->fresh());

        expect($revisions)->toHaveCount(2)
            ->and($revisions->first()->revision_number)->toBe(2);
    });

    it('fetches a specific revision by number', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Test Page');

        $rev = app(RevisionService::class)->getRevision($page->fresh(), 1);

        expect($rev)->toBeInstanceOf(PageRevision::class)
            ->and($rev->revision_number)->toBe(1);
    });

    it('throws 404 for a missing revision number', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Test Page');

        expect(fn () => app(RevisionService::class)->getRevision($page->fresh(), 99))
            ->toThrow(NotFoundHttpException::class);
    });

    it('returns a structured diff array with insert and delete entries', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $content1 = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Hello world"}]}]}';
        $content2 = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Hello Paige"}]}]}';

        $page = app(CreatePage::class)->handle($space, $user, 'Test', $content1);
        app(PublishPage::class)->handle($page->fresh(), $user, null, $content2);

        $service = app(RevisionService::class);
        $revA = $service->getRevision($page->fresh(), 1);
        $revB = $service->getRevision($page->fresh(), 2);
        $diff = $service->diff($revA, $revB);

        $tags = collect($diff)->pluck('tag')->unique()->values()->all();
        expect($diff)->toBeArray()
            ->and($tags)->toContain('delete')
            ->and($tags)->toContain('insert');
    });
});
