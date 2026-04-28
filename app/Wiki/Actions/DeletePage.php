<?php

namespace App\Wiki\Actions;

use App\Models\Page;

class DeletePage
{
    public function handle(Page $page): void
    {
        $page->delete();
    }
}
