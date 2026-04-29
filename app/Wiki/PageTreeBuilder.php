<?php

namespace App\Wiki;

use App\Enums\PageStatus;
use App\Models\Space;

class PageTreeBuilder
{
    /**
     * Build a nested tree for the given space.
     * One DB query; O(n) assembly in PHP using references.
     *
     * @return list<array{id: int, title: string, slug: string, position: int, isDraft: bool, children: list<mixed>}>
     */
    public function build(Space $space, bool $includeDrafts = false): array
    {
        $query = $space->pages()
            ->orderBy('position')
            ->select(['id', 'title', 'slug', 'parent_id', 'position', 'status']);

        if (! $includeDrafts) {
            $query->where('status', PageStatus::Published);
        }

        $pages = $query->get();

        /** @var array<int, array{id: int, title: string, slug: string, position: int, isDraft: bool, children: list<mixed>}> $nodes */
        $nodes = [];

        foreach ($pages as $p) {
            $nodes[$p->id] = [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'position' => $p->position,
                'isDraft' => $p->status === PageStatus::Draft,
                'children' => [],
            ];
        }

        $tree = [];

        foreach ($pages as $p) {
            if ($p->parent_id !== null && isset($nodes[$p->parent_id])) {
                $nodes[$p->parent_id]['children'][] = &$nodes[$p->id];
            } else {
                $tree[] = &$nodes[$p->id];
            }
        }

        return $tree;
    }
}
