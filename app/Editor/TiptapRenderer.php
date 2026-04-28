<?php

namespace App\Editor;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Marks\Link;
use Tiptap\Marks\Underline;

class TiptapRenderer
{
    public function render(?string $json): string
    {
        if ($json === null || trim($json) === '') {
            return '';
        }

        if (json_decode($json, true) === null) {
            return '';
        }

        try {
            return (new Editor(['extensions' => [new StarterKit, new Underline, new Link]]))
                ->setContent($json)
                ->getHTML();
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Cache key: page:{page_id}:html - 24h TTL, invalidated by PublishPage action.
     */
    public function renderCached(Page $page): string
    {
        return Cache::remember(
            "page:$page->id:html",
            now()->addHours(24),
            fn () => $this->render($page->content)
        );
    }
}
