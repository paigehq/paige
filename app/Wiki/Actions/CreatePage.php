<?php

namespace App\Wiki\Actions;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Exceptions\SlugExhaustedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePage
{
    public function __construct(protected CreateRevision $createRevision)
    {
        //
    }

    public function handle(
        Space $space,
        User $author,
        string $title,
        ?string $content = null,
        ?Page $parent = null,
        ?string $changeSummary = null,
    ): Page {
        return DB::transaction(function () use ($space, $author, $title, $content, $parent, $changeSummary) {
            $page = Page::create([
                'space_id' => $space->id,
                'parent_id' => $parent?->id,
                'title' => $title,
                'slug' => $this->generateSlug($space, $title),
                'content' => $content,
                'status' => PageStatus::Draft,
                'author_id' => $author->id,
                'last_editor_id' => $author->id,
                'revision_number' => 1,
                'position' => 0,
            ]);

            $this->createRevision->handle($page, $author, $changeSummary);

            return $page;
        });
    }

    protected function generateSlug(Space $space, string $title): string
    {
        $base = Str::slug($title);

        if (! Page::withTrashed()->where('space_id', $space->id)->where('slug', $base)->exists()) {
            return $base;
        }

        for ($i = 2; $i <= 10; $i++) {
            $candidate = "$base-$i";

            if (! Page::withTrashed()->where('space_id', $space->id)->where('slug', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new SlugExhaustedException($base);
    }
}
