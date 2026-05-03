<?php

namespace App\Http\Controllers;

use App\Editor\TiptapRenderer;
use App\Enums\PageStatus;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Models\Space;
use App\Models\Tag;
use App\Models\User;
use App\Permission\Exceptions\PermissionDeniedException;
use App\Permission\PermissionChecker;
use App\Wiki\Actions\SaveDraft;
use App\Wiki\PageService;
use App\Wiki\PageTreeBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PageController extends Controller
{
    public function __construct(
        protected readonly TiptapRenderer $renderer,
        protected readonly PageTreeBuilder $treeBuilder,
        protected readonly PageService $pageService,
        protected readonly SaveDraft $saveDraft,
        protected readonly PermissionChecker $permissionChecker,
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
            ->map(fn (Page $p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug])
            ->values()
            ->all();

        $children = $page->children()
            ->where('status', PageStatus::Published)
            ->orderBy('position')
            ->get(['id', 'title', 'slug', 'position'])
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'position' => $p->position,
            ])
            ->values()
            ->all();

        $page->load(['lastEditor', 'tags', 'media']);

        /** @var User|null $authUser */
        $authUser = auth()->user();

        // getMedia() accepts a single collection name; load all media via the relation
        // and map over it so images and attachments are combined in one pass.
        $attachments = $page->media
            ->map(function (Media $media) use ($authUser, $space): array {
                $isImage = $media->collection_name === 'images';
                $uploaderId = $media->getCustomProperty('uploader_id');
                $canDelete = $authUser !== null && (
                    $authUser->id === $uploaderId ||
                    $this->permissionChecker->can($authUser, 'admin', $space)
                );
                $thumbnailUrl = null;
                if ($isImage && $media->hasGeneratedConversion('thumbnail')) {
                    $thumbnailUrl = URL::temporarySignedRoute(
                        'attachments.download',
                        now()->addMinutes(60),
                        ['media' => $media->id, 'conversion' => 'thumbnail'],
                    );
                }

                return [
                    'id' => $media->id,
                    'filename' => $media->file_name,
                    'size' => $media->human_readable_size,
                    'mimeType' => $media->mime_type,
                    'isImage' => $isImage,
                    'downloadUrl' => URL::temporarySignedRoute(
                        'attachments.download',
                        now()->addMinutes(60),
                        ['media' => $media->id],
                    ),
                    'thumbnailUrl' => $thumbnailUrl,
                    'canDelete' => $canDelete,
                ];
            })
            ->values()
            ->all();

        $tags = $page->tags->map(fn (Tag $tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
        ])
            ->values()
            ->all();

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
                'attachments' => $attachments,
                'tags' => $tags,
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

    /**
     * @throws PermissionDeniedException
     */
    public function store(StorePageRequest $request, Space $space): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->permissionChecker->authorize($user, 'write', $space);

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

    /**
     * @throws PermissionDeniedException
     */
    public function update(UpdatePageRequest $request, Space $space, Page $page): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->permissionChecker->authorize($user, 'write', $space);

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

    /**
     * @throws PermissionDeniedException
     */
    public function destroy(Request $request, Space $space, Page $page): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->permissionChecker->authorize($user, 'write', $space);

        $this->pageService->deletePage($page);

        return redirect()->route('spaces.show', $space);
    }
}
