<?php

namespace App\Http\Controllers;

use App\Editor\TiptapRenderer;
use App\Models\Page;
use App\Models\Space;
use App\Wiki\PageTreeBuilder;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly TiptapRenderer $renderer,
        private readonly PageTreeBuilder $treeBuilder,
    ) {}

    public function show(Space $space, Page $page): Response
    {
        abort(404);
    }
}
