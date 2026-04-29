<?php

namespace App\Http\Controllers;

use App\Editor\TiptapRenderer;
use App\Enums\PageStatus;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\SaveDraft;
use App\Wiki\PageService;
use App\Wiki\PageTreeBuilder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        protected readonly TiptapRenderer $renderer,
        protected readonly PageTreeBuilder $treeBuilder,
        protected readonly PageService $pageService,
        protected readonly SaveDraft $saveDraft,
    ) {
        //
    }

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
            'tree' => $this->treeBuilder->build($space, auth()->check()),
        ]);
    }

    public function create(Space $space): Response
    {
        return Inertia::render('pages/Create', [
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
                'slug' => $space->slug,
                'description' => $space->description,
            ],
            'tree' => $this->treeBuilder->build($space, auth()->check()),
        ]);
    }

    public function store(StorePageRequest $request, Space $space): RedirectResponse
    {
        $parent = $request->integer('parent_id') > 0
            ? Page::find($request->integer('parent_id'))
            : null;

        /** @var User $user */
        $user = $request->user();

        $page = $this->pageService->createPage(
            $space,
            $user,
            (string) $request->string('title'),
            (string) $request->string('content') ?: null,
            $parent,
        );

        return redirect()->route('pages.edit', ['space' => $space, 'page' => $page]);
    }

    public function edit(Space $space, Page $page): Response
    {
        return Inertia::render('pages/Edit', [
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
                'content' => $page->content,
                'status' => $page->status->value,
                'revisionNumber' => $page->revision_number,
            ],
            'tree' => $this->treeBuilder->build($space, auth()->check()),
        ]);
    }

    public function update(UpdatePageRequest $request, Space $space, Page $page): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->input('action') === 'publish') {
            $this->pageService->publishPage(
                $page,
                $user,
                (string) $request->string('title'),
                (string) $request->string('content') ?: null,
                (string) $request->string('change_summary') ?: null,
            );
        } else {
            $this->saveDraft->handle(
                $page,
                $user,
                (string) $request->string('title'),
                (string) $request->string('content') ?: null,
            );
        }

        return back();
    }

    public function destroy(Space $space, Page $page): RedirectResponse
    {
        $this->pageService->deletePage($page);

        return redirect()->route('spaces.show', $space);
    }
}
