<?php

namespace App\Wiki\Actions;

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;

class CreateRevision
{
    /**
     * Insert a new page_revisions row from the page's current state.
     * MUST be called inside the caller's DB::transaction() - never standalone.
     */
    public function handle(Page $page, User $editor, ?string $changeSummary = null): PageRevision
    {
        return PageRevision::create([
            'page_id' => $page->id,
            'title' => $page->title,
            'content' => $page->content,
            'editor_id' => $editor->id,
            'revision_number' => $page->revision_number,
            'change_summary' => $changeSummary,
            'created_at' => now(),
        ]);
    }
}
