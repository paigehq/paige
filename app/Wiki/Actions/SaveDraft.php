<?php

namespace App\Wiki\Actions;

use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SaveDraft
{
    /**
     * @param  array<int, string>|null  $tagNames
     *
     * @throws Throwable
     */
    public function handle(
        Page $page,
        User $editor,
        ?string $title,
        ?string $content,
        ?array $tagNames = null,
    ): Page {
        DB::transaction(function () use ($page, $editor, $title, $content, $tagNames): void {
            $page->title = $title ?? $page->title;
            $page->content = $content ?? $page->content;
            $page->last_editor_id = $editor->id;
            $page->save();

            if ($tagNames !== null) {
                $tagIds = collect($tagNames)
                    ->filter()
                    ->map(fn (string $name): int => Tag::firstOrCreate(
                        ['slug' => Str::slug($name)],
                        ['name' => trim($name)],
                    )->id)
                    ->all();

                $page->tags()->sync($tagIds);
            }
        });

        return $page;
    }
}
