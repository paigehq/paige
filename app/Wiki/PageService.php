<?php

namespace App\Wiki;

use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\DeletePage;
use App\Wiki\Actions\MovePage;
use App\Wiki\Actions\PublishPage;
use App\Wiki\Actions\SaveDraft;

class PageService
{
    public function __construct(
        protected readonly CreatePage $createPage,
        protected readonly PublishPage $publishPage,
        protected readonly MovePage $movePage,
        protected readonly DeletePage $deletePage,
        protected readonly SaveDraft $saveDraft,
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

    public function movePage(Page $page, ?Page $newParent): Page
    {
        return $this->movePage->handle($page, $newParent);
    }

    public function deletePage(Page $page): void
    {
        $this->deletePage->handle($page);
    }

    public function saveDraft(Page $page, User $editor, ?string $title, ?string $content): Page
    {
        return $this->saveDraft->handle($page, $editor, $title, $content);
    }
}
