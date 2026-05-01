<?php

namespace App\Search;

use App\Editor\TiptapExtractor;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Permission\PermissionChecker;

class SearchService
{
    public function __construct(
        protected readonly PermissionChecker $permissionChecker,
        protected readonly TiptapExtractor $extractor,
    ) {
        //
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function search(string $query, ?User $user = null): array
    {
        if (trim($query) === '') {
            return [];
        }

        $pages = Page::search($query)->get();

        $pages->load(['space' => fn ($q) => $q->withTrashed(), 'tags']);

        return $pages
            ->filter(fn (Page $page) => $page->space !== null && $this->canRead($user, $page->space))
            ->map(fn (Page $page) => [
                'title' => $page->title,
                'excerpt' => $this->excerpt($page->content, $query),
                'spaceName' => $page->space->name,
                'spaceSlug' => $page->space->slug,
                'pageSlug' => $page->slug,
                'updatedAt' => $page->updated_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    protected function canRead(?User $user, Space $space): bool
    {
        if ($space->deleted_at !== null) {
            return false;
        }

        if ($user === null) {
            return $space->visibility === SpaceVisibility::Public;
        }

        return $this->permissionChecker->can($user, 'read', $space);
    }

    protected function excerpt(?string $json, string $query): string
    {
        $text = $this->extractor->plainText($json);

        if ($text === '') {
            return '';
        }

        $pos = mb_stripos($text, $query);

        if ($pos === false) {
            return mb_substr($text, 0, 150);
        }

        $start = max(0, $pos - 50);
        $snippet = mb_substr($text, $start, mb_strlen($query) + 150);
        $highlighted = preg_replace(
            '/('.preg_quote($query, '/').')/ii',
            '<mark>$1</mark>',
            $snippet,
        );

        return ($start > 0 ? '...' : '').($highlighted ?? $snippet);
    }
}
