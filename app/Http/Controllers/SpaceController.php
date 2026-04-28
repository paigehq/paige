<?php

namespace App\Http\Controllers;

use App\Editor\TiptapRenderer;
use App\Models\Space;
use App\Wiki\PageTreeBuilder;
use Inertia\Inertia;
use Inertia\Response;

class SpaceController extends Controller
{
    public function __construct(
        private readonly TiptapRenderer $renderer,
        private readonly PageTreeBuilder $treeBuilder,
    ) {}

    public function show(Space $space): Response
    {
        $tree = $this->treeBuilder->build($space);

        return Inertia::render('spaces/Show', [
            'space' => [
                'id' => $space->id,
                'name' => $space->name,
                'slug' => $space->slug,
                'description' => $space->description,
            ],
            'page' => null,
            'tree' => $tree,
        ]);
    }
}
