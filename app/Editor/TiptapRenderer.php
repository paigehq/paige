<?php

namespace App\Editor;

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
}
