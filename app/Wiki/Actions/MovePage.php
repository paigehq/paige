<?php

namespace App\Wiki\Actions;

use App\Models\Page;
use App\Wiki\Exceptions\CircularReferenceException;
use Illuminate\Support\Facades\DB;

class MovePage
{
    public function handle(Page $page, ?Page $newParent): Page
    {
        if ($newParent !== null) {
            if ($newParent->id === $page->id) {
                throw new CircularReferenceException;
            }

            // DB-level check: is $newParent a descendant of $page?
            // Uses WITH RECURSIVE CTE via the adjacency-list package.
            if ($page->descendants()->where('id', $newParent->id)->exists()) {
                throw new CircularReferenceException;
            }
        }

        DB::transaction(function () use ($page, $newParent): void {
            $page->parent_id = $newParent?->id;
            $page->save();
        });

        return $page->refresh();
    }
}
