<?php

namespace App\Editor;

use Throwable;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Marks\Link;
use Tiptap\Marks\Underline;

class TiptapExtractor
{
    public function plainText(?string $json): string
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
                ->getText(['blockSeparator' => "\n\n"]);
        } catch (Throwable) {
            return '';
        }
    }
}
