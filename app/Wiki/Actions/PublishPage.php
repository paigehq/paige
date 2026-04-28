<?php

namespace App\Wiki\Actions;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublishPage
{
    public function __construct(protected CreateRevision $createRevision)
    {
        //
    }

    public function handle(
        Page $page,
        User $editor,
        ?string $title = null,
        ?string $content = null,
        ?string $changeSummary = null,
    ): Page {
        DB::transaction(function () use ($page, $editor, $title, $content, $changeSummary) {
            $page->title = $title ?? $page->title;
            $page->content = $content ?? $page->content;
            $page->status = PageStatus::Published;
            $page->last_editor_id = $editor->id;
            $page->revision_number++;
            $page->save();

            $this->createRevision->handle($page, $editor, $changeSummary);
        });

        // Invalidate cache after commit - outside transaction so a rollback
        // does not cause a spurious cache eviction.
        Cache::forget("page:$page->id:html");

        return $page->refresh();
    }
}
