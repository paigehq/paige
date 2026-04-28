<?php

namespace App\Wiki;

use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\PublishPage;

class PageService
{
    public function __construct(
        protected readonly CreatePage $createPage,
        protected readonly PublishPage $publishPage,
    ) {
        //
    }

    public function createPage(
        Space $space,
        User $author,
        string $title,
        ?string $content = null,
        ?Page $parent = null,
        ?string $changeSummary = null,
    ): Page {
        return $this->createPage->handle($space, $author, $title, $content, $parent, $changeSummary);
    }

    public function publishPage(
        Page $page,
        User $editor,
        ?string $title = null,
        ?string $content = null,
        ?string $changeSummary = null,
    ): Page {
        return $this->publishPage->handle($page, $editor, $title, $content, $changeSummary);
    }
}
