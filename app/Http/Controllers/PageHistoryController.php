<?php

namespace App\Http\Controllers;

use App\Editor\TiptapRenderer;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\Space;
use App\Wiki\PageTreeBuilder;
use App\Wiki\RevisionService;
use Inertia\Inertia;
use Inertia\Response;

class PageHistoryController extends Controller
{
    public function __construct(
        protected readonly RevisionService $revisionService,
        protected readonly TiptapRenderer $renderer,
        protected readonly PageTreeBuilder $treeBuilder,
    ) {
        //
    }

    public function index(Space $space, Page $page): Response
    {
        $revisions = $this->revisionService->getRevisions($page);

        return Inertia::render('pages/History', [
            'space' => $this->spaceShape($space),
            'page' => $this->pageShape($page),
            'tree' => $this->treeBuilder->build($space, auth()->check()),
            'revisions' => $revisions->map(fn (PageRevision $r) => [
                'number' => $r->revision_number,
                'editorName' => $r->editor->name ?? 'Unknown',
                'changeSummary' => $r->change_summary,
                'createdAt' => $r->created_at->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function show(Space $space, Page $page, int $revision): Response
    {
        $rev = $this->revisionService->getRevision($page, $revision);

        return Inertia::render('pages/RevisionDetail', [
            'space' => $this->spaceShape($space),
            'page' => $this->pageShape($page),
            'tree' => $this->treeBuilder->build($space, auth()->check()),
            'revision' => [
                'number' => $rev->revision_number,
                'title' => $rev->title,
                'html' => $this->renderer->render($rev->content ?? ''),
                'editorName' => $rev->editor->name ?? 'Unknown',
                'createdAt' => $rev->created_at->toIso8601String(),
            ],
        ]);
    }

    public function diff(Space $space, Page $page, int $a, int $b): Response
    {
        $revA = $this->revisionService->getRevision($page, $a);
        $revB = $this->revisionService->getRevision($page, $b);

        return Inertia::render('pages/Diff', [
            'space' => $this->spaceShape($space),
            'page' => $this->pageShape($page),
            'tree' => $this->treeBuilder->build($space, auth()->check()),
            'revisionA' => [
                'number' => $revA->revision_number,
                'editorName' => $revA->editor->name ?? 'Unknown',
                'createdAt' => $revA->created_at->toIso8601String(),
            ],
            'revisionB' => [
                'number' => $revB->revision_number,
                'editorName' => $revB->editor->name ?? 'Unknown',
                'createdAt' => $revB->created_at->toIso8601String(),
            ],
            'diff' => $this->revisionService->diff($revA, $revB),
        ]);
    }

    /**
     * @return array{id: int, name: string, slug: string, description: string|null}
     */
    protected function spaceShape(Space $space): array
    {
        return [
            'id' => $space->id,
            'name' => $space->name,
            'slug' => $space->slug,
            'description' => $space->description,
        ];
    }

    /**
     * @return array{id: int, title: string, slug: string, status: string}
     */
    protected function pageShape(Page $page): array
    {
        return ['id' => $page->id, 'title' => $page->title, 'slug' => $page->slug, 'status' => $page->status->value];
    }
}
