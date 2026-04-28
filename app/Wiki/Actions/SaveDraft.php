<?php

namespace App\Wiki\Actions;

use App\Models\Page;
use App\Models\User;

class SaveDraft
{
    public function handle(Page $page, User $editor, ?string $title, ?string $content): Page
    {
        $page->title = $title ?? $page->title;
        $page->content = $content ?? $page->content;
        $page->last_editor_id = $editor->id;
        $page->save();

        return $page;
    }
}
