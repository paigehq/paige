<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Space;
use App\Models\Tag;
use App\Models\User;
use App\Permission\PermissionChecker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(protected readonly PermissionChecker $checker)
    {
        //
    }

    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        $readableSpaceIds = $this->readableSpaceIds($user);

        $tags = Tag::withCount([
            'pages' => fn (Builder $query) => $query
                ->where('status', PageStatus::Published)
                ->whereIn('space_id', $readableSpaceIds),
        ])
            ->orderByDesc('pages_count')
            ->paginate(50);

        return Inertia::render('tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function show(Request $request, Tag $tag): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        $readableSpaceIds = $this->readableSpaceIds($user);

        $pages = $tag->pages()
            ->with(['space', 'lastEditor'])
            ->whereIn('space_id', $readableSpaceIds)
            ->where('status', PageStatus::Published)
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->through(fn (Page $page) => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'spaceName' => $page->space->name,
                'spaceSlug' => $page->space->slug,
                'lastEditorName' => $page->lastEditor?->name,
                'updatedAt' => $page->updated_at->toIso8601String(),
            ]);

        return Inertia::render('tags/Show', [
            'tag' => ['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug],
            'pages' => $pages,
        ]);
    }

    /**
     * @return Collection<int|string, mixed>
     */
    protected function readableSpaceIds(?User $user): Collection
    {
        return Space::all()
            ->filter(function (Space $space) use ($user): bool {
                if ($user === null) {
                    return $space->visibility === SpaceVisibility::Public;
                }

                return $this->checker->can($user, 'read', $space);
            })
            ->pluck('id');
    }
}
