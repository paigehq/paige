<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSpaceRequest;
use App\Http\Requests\Admin\UpdateSpaceRequest;
use App\Models\Space;
use App\Models\User;
use App\Space\Actions\CreateSpace;
use App\Space\SpaceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SpaceController extends Controller
{
    public function __construct(
        protected readonly CreateSpace $createSpace,
        protected readonly SpaceService $spaceService,
    ) {
        //
    }

    public function index(): Response
    {
        $spaces = Space::withTrashed()
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('admin/spaces/Index', [
            'spaces' => $spaces->through(fn (Space $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'description' => $s->description,
                'visibility' => $s->visibility->value,
                'archived' => $s->trashed(),
                'createdAt' => $s->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/spaces/Create');
    }

    public function store(StoreSpaceRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{name: string, description: ?string, visibility: ?string} $data */
        $data = $request->validated();

        $this->createSpace->handle($data, $user);

        return redirect()->route('admin.spaces.index');
    }

    public function edit(Space $space): Response
    {
        return Inertia::render('admin/spaces/Edit', [
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
                'slug' => $space->slug,
                'description' => $space->description,
                'visibility' => $space->visibility->value,
            ],
        ]);
    }

    public function update(UpdateSpaceRequest $request, Space $space): RedirectResponse
    {
        $this->spaceService->update($space, $request->validated());

        return redirect()->route('admin.spaces.index');
    }

    public function destroy(Space $space): RedirectResponse
    {
        $this->spaceService->archive($space);

        return redirect()->route('admin.spaces.index');
    }
}
