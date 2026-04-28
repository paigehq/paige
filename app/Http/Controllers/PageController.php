<?php

namespace App\Http\Controllers;

use App\Editor\TiptapRenderer;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Space;
use App\Wiki\PageTreeBuilder;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        protected readonly TiptapRenderer $renderer,
        protected readonly PageTreeBuilder $treeBuilder,
    ) {}

    public function show(Space $space, Page $page): Response
    {
        if ($page->status !== PageStatus::Published) {
            abort(404);
        }

        $breadcrumb = $page->ancestorsAndSelf()
            ->orderBy('depth')
            ->select(['id', 'title', 'slug'])
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug])
            ->values()
            ->all();

        $children = $page->children()
            ->where('status', PageStatus::Published)
            ->orderBy('position')
            ->get(['id', 'title', 'slug', 'position'])
            ->map(fn ($p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug, 'position' => $p->position])
            ->values()
            ->all();

        $page->load('lastEditor');

        return Inertia::render('pages/Show', [
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
                'slug' => $space->slug,
                'description' => $space->description,
            ],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'html' => $this->renderer->renderCached($page),
                'breadcrumb' => $breadcrumb,
                'children' => $children,
                'lastEditor' => $page->lastEditor
                    ? ['id' => $page->lastEditor->id, 'name' => $page->lastEditor->name]
                    : null,
                'updatedAt' => $page->updated_at->toIso8601String(),
            ],
            'tree' => $this->treeBuilder->build($space),
        ]);
    }
}
