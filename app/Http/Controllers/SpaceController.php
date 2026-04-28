<?php

namespace App\Http\Controllers;

use App\Editor\TiptapRenderer;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Space;
use App\Wiki\PageTreeBuilder;
use Inertia\Inertia;
use Inertia\Response;

class SpaceController extends Controller
{
    public function __construct(
        protected readonly TiptapRenderer $renderer,
        protected readonly PageTreeBuilder $treeBuilder,
    ) {}

    public function show(Space $space): Response
    {
        $tree = $this->treeBuilder->build($space);

        $firstPage = $space->pages()
            ->where('status', PageStatus::Published)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->with('lastEditor')
            ->first();

        $pageData = null;

        if ($firstPage !== null) {
            $breadcrumb = $firstPage->ancestorsAndSelf()
                ->orderBy('depth')
                ->select(['id', 'title', 'slug'])
                ->get()
                ->map(fn (Page $p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug])
                ->values()
                ->all();

            $children = $firstPage->children()
                ->where('status', PageStatus::Published)
                ->orderBy('position')
                ->get(['id', 'title', 'slug', 'position'])
                ->map(fn (Page $p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug, 'position' => $p->position])
                ->values()
                ->all();

            $pageData = [
                'id' => $firstPage->id,
                'title' => $firstPage->title,
                'slug' => $firstPage->slug,
                'html' => $this->renderer->renderCached($firstPage),
                'breadcrumb' => $breadcrumb,
                'children' => $children,
                'lastEditor' => $firstPage->lastEditor
                    ? ['id' => $firstPage->lastEditor->id, 'name' => $firstPage->lastEditor->name]
                    : null,
                'updatedAt' => $firstPage->updated_at->toIso8601String(),
            ];
        }

        return Inertia::render('spaces/Show', [
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
                'slug' => $space->slug,
                'description' => $space->description,
            ],
            'page' => $pageData,
            'tree' => $tree,
        ]);
    }
}
