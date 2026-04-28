<?php

use App\Editor\TiptapExtractor;

// JSON fixture helpers — "x_" prefixed to avoid collision with r_* helpers from TiptapRendererTest
function x_doc(array ...$nodes): string
{
    return json_encode(['type' => 'doc', 'content' => $nodes]);
}

function x_paragraph(string $text): array
{
    return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
}

function x_heading(int $level, string $text): array
{
    return [
        'type' => 'heading',
        'attrs' => ['level' => $level],
        'content' => [['type' => 'text', 'text' => $text]],
    ];
}

function x_bulletList(string ...$items): array
{
    return [
        'type' => 'bulletList',
        'content' => array_map(
            fn ($item) => [
                'type' => 'listItem',
                'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $item]]]],
            ],
            $items
        ),
    ];
}

function x_codeBlock(string $code): array
{
    return ['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => $code]]];
}

function x_markedParagraph(string $text, string $markType): array
{
    return [
        'type' => 'paragraph',
        'content' => [['type' => 'text', 'text' => $text, 'marks' => [['type' => $markType]]]],
    ];
}

describe('TiptapExtractor::plainText()', function () {
    it('returns empty string for empty input', function () {
        expect((new TiptapExtractor)->plainText(''))->toBe('');
    });

    it('returns empty string for null input', function () {
        expect((new TiptapExtractor)->plainText(null))->toBe('');
    });

    it('returns empty string for malformed JSON', function () {
        expect((new TiptapExtractor)->plainText('{bad json}'))->toBe('');
    });

    it('extracts text from a paragraph', function () {
        $text = (new TiptapExtractor)->plainText(x_doc(x_paragraph('Hello world')));
        expect($text)->toContain('Hello world');
    });

    it('extracts text from a heading', function () {
        $text = (new TiptapExtractor)->plainText(x_doc(x_heading(1, 'Big Title')));
        expect($text)->toContain('Big Title');
    });

    it('produces no HTML tags', function () {
        $text = (new TiptapExtractor)->plainText(
            x_doc(x_heading(2, 'Title'), x_paragraph('Body'))
        );
        expect($text)->not->toContain('<')->not->toContain('>');
    });

    it('extracts text from all bullet list items', function () {
        $text = (new TiptapExtractor)->plainText(
            x_doc(x_bulletList('Alpha', 'Beta', 'Gamma'))
        );
        expect($text)->toContain('Alpha')->toContain('Beta')->toContain('Gamma');
    });

    it('extracts text from a code block', function () {
        $text = (new TiptapExtractor)->plainText(x_doc(x_codeBlock('x = 1 + 2')));
        expect($text)->toContain('x = 1 + 2');
    });

    it('extracts plain text from bold mark — no HTML tags', function () {
        $text = (new TiptapExtractor)->plainText(x_doc(x_markedParagraph('Strong', 'bold')));
        expect($text)->toContain('Strong')->not->toContain('<strong>');
    });

    it('extracts plain text from italic mark — no HTML tags', function () {
        $text = (new TiptapExtractor)->plainText(x_doc(x_markedParagraph('Slanted', 'italic')));
        expect($text)->toContain('Slanted')->not->toContain('<em>');
    });
});
