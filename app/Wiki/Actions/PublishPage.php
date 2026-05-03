<?php

namespace App\Wiki\Actions;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\PageSlugHistory;
use App\Models\Space;
use App\Models\Tag;
use App\Models\User;
use App\Wiki\Exceptions\SlugExhaustedException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PublishPage
{
    public function __construct(protected CreateRevision $createRevision)
    {
        //
    }

    /**
     * @param  array<int, string>|null  $tagNames
     *
     * @throws Throwable
     */
    public function handle(
        Page $page,
        User $editor,
        ?string $title = null,
        ?string $content = null,
        ?string $changeSummary = null,
        ?array $tagNames = null,
    ): Page {
        DB::transaction(function () use ($page, $editor, $title, $content, $changeSummary, $tagNames): void {
            $oldSlug = $page->slug;

            $page->title = $title ?? $page->title;
            $page->content = $content ?? $page->content;
            $page->status = PageStatus::Published;
            $page->last_editor_id = $editor->id;
            $page->revision_number++;

            if ($title !== null) {
                $newSlug = $this->generateSlug($page->space, $title, $page->id);
                $page->slug = $newSlug;
            }

            $page->save();

            if (isset($newSlug) && $newSlug !== $oldSlug) {
                PageSlugHistory::create([
                    'page_id' => $page->id,
                    'slug' => $oldSlug,
                    'created_at' => now(),
                ]);
            }

            $this->createRevision->handle($page, $editor, $changeSummary);

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

        // Invalidate cache after commit - outside transaction so a rollback
        // does not cause a spurious cache eviction.
        Cache::forget("page:$page->id:html");
        $page->refresh();
        $page->searchable();

        return $page;
    }

    protected function generateSlug(Space $space, string $title, int $excludePageId): string
    {
        $base = Str::slug($title);

        if (! Page::withTrashed()
            ->where('space_id', $space->id)
            ->where('slug', $base)
            ->where('id', '!=', $excludePageId)
            ->exists()) {
            return $base;
        }

        for ($i = 2; $i <= 10; $i++) {
            $candidate = "$base-$i";

            if (! Page::withTrashed()
                ->where('space_id', $space->id)
                ->where('slug', $candidate)
                ->where('id', '!=', $excludePageId)
                ->exists()) {
                return $candidate;
            }
        }

        throw new SlugExhaustedException($base);
    }
}
