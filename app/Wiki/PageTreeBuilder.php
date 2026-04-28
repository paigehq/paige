<?php

namespace App\Wiki;

use App\Enums\PageStatus;
use App\Models\Space;

class PageTreeBuilder
{
    /**
     * Build a nested tree of published pages for the given space.
     * One DB query; tree assembly in 0(n) in PHP using references.
     *
     * @return list<array{id: int, title: string, slug: string, position: int, children: list<mixed>}>
     */
    public function build(Space $space): array
    {
        $pages = $space->pages()
            ->where('status', PageStatus::Published)
            ->orderBy('position')
            ->get(['id', 'title', 'slug', 'parent_id', 'position']);

        /** @var array<int, array{id: int, title: string, slug: string, position: int, children: list<mixed>}> $nodes */
        $nodes = [];

        foreach ($pages as $p) {
            $nodes[$p->id] = [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'position' => $p->position,
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
