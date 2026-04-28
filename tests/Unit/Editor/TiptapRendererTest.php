<?php

use App\Editor\TiptapRenderer;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class)->use(RefreshDatabase::class);

// JSON fixture helpers — prefixed "r_" to avoid collisions when both test files run together
function r_doc(array ...$nodes): string
{
    return json_encode(['type' => 'doc', 'content' => $nodes]);
}

function r_paragraph(array ...$inlines): array
{
    return ['type' => 'paragraph', 'content' => $inlines];
}

function r_text(string $text, array $marks = []): array
{
    $node = ['type' => 'text', 'text' => $text];
    if ($marks !== []) {
        $node['marks'] = $marks;
    }

    return $node;
}

function r_mark(string $type, array $attrs = []): array
{
    return $attrs !== [] ? ['type' => $type, 'attrs' => $attrs] : ['type' => $type];
}

function r_heading(int $level, string $text): array
{
    return [
        'type' => 'heading',
        'attrs' => ['level' => $level],
        'content' => [['type' => 'text', 'text' => $text]],
    ];
}

function r_bulletList(string ...$items): array
{
    return [
        'type' => 'bulletList',
        'content' => array_map(
            fn ($item) => [
                'type' => 'listItem',
                'content' => [r_paragraph(r_text($item))],
            ],
            $items
        ),
    ];
}

function r_orderedList(string ...$items): array
{
    return [
        'type' => 'orderedList',
        'content' => array_map(
            fn ($item) => [
                'type' => 'listItem',
                'content' => [r_paragraph(r_text($item))],
            ],
            $items
        ),
    ];
}

function r_codeBlock(string $code): array
{
    return ['type' => 'codeBlock', 'content' => [['type' => 'text', 'text' => $code]]];
}

function r_blockquote(array ...$content): array
{
    return ['type' => 'blockquote', 'content' => $content];
}

describe('TiptapRenderer::render()', function () {
    it('returns empty string for empty input', function () {
        expect((new TiptapRenderer)->render(''))->toBe('');
    });

    it('returns empty string for null input', function () {
        expect((new TiptapRenderer)->render(null))->toBe('');
    });

    it('returns empty string for malformed JSON', function () {
        expect((new TiptapRenderer)->render('{not valid json}'))->toBe('');
    });

    it('renders a paragraph', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_paragraph(r_text('Hello world'))));
        expect($html)->toContain('<p>Hello world</p>');
    });

    it('renders h1', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_heading(1, 'Big Title')));
        expect($html)->toContain('<h1>Big Title</h1>');
    });

    it('renders h2', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_heading(2, 'Section')));
        expect($html)->toContain('<h2>Section</h2>');
    });

    it('renders h3', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_heading(3, 'Subsection')));
        expect($html)->toContain('<h3>Subsection</h3>');
    });

    it('renders a bullet list', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_bulletList('Alpha', 'Beta')));
        expect($html)
            ->toContain('<ul>')
            ->toContain('<li>')
            ->toContain('Alpha')
            ->toContain('Beta');
    });

    it('renders an ordered list', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_orderedList('First', 'Second')));
        expect($html)
            ->toContain('<ol>')
            ->toContain('<li>')
            ->toContain('First')
            ->toContain('Second');
    });

    it('renders a code block', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_codeBlock('x = 1 + 2')));
        expect($html)
            ->toContain('<pre>')
            ->toContain('<code>')
            ->toContain('x = 1 + 2');
    });

    it('renders bold mark', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_paragraph(r_text('Bold', [r_mark('bold')]))));
        expect($html)->toContain('<strong>Bold</strong>');
    });

    it('renders italic mark', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_paragraph(r_text('Slanted', [r_mark('italic')]))));
        expect($html)->toContain('<em>Slanted</em>');
    });

    it('renders underline mark', function () {
        $html = (new TiptapRenderer)->render(r_doc(r_paragraph(r_text('Under', [r_mark('underline')]))));
        expect($html)->toContain('<u>Under</u>');
    });

    it('renders link mark', function () {
        $html = (new TiptapRenderer)->render(
            r_doc(r_paragraph(r_text('Visit', [r_mark('link', ['href' => 'https://example.com'])])))
        );
        expect($html)
            ->toContain('<a')
            ->toContain('href="https://example.com"')
            ->toContain('Visit');
    });

    it('renders nested blockquotes', function () {
        $inner = r_blockquote(r_paragraph(r_text('Deep')));
        $outer = r_blockquote($inner);
        $html = (new TiptapRenderer)->render(r_doc($outer));
        expect(substr_count($html, '<blockquote>'))->toBe(2);
    });
});

describe('TiptapRenderer::renderCached()', function () {
    beforeEach(fn () => Cache::flush());

    it('returns rendered HTML for a page', function () {
        $page = new Page;
        $page->id = 1;
        $page->content = r_doc(r_paragraph(r_text('Cached content')));

        expect((new TiptapRenderer)->renderCached($page))->toContain('Cached content');
    });

    it('stores the result under the page:{id}:html key', function () {
        $page = new Page;
        $page->id = 42;
        $page->content = r_doc(r_paragraph(r_text('Keyed')));

        (new TiptapRenderer)->renderCached($page);

        expect(Cache::has('page:42:html'))->toBeTrue();
    });

    it('calls render() only once when called twice for the same page', function () {
        $renderer = new class extends TiptapRenderer
        {
            public int $renderCallCount = 0;

            public function render(?string $json): string
            {
                $this->renderCallCount++;

                return parent::render($json);
            }
        };

        $page = new Page;
        $page->id = 7;
        $page->content = r_doc(r_paragraph(r_text('Once')));

        $renderer->renderCached($page);
        $renderer->renderCached($page);

        expect($renderer->renderCallCount)->toBe(1);
    });

    it('returns empty string for a page with null content', function () {
        $page = new Page;
        $page->id = 2;
        $page->content = null;

        expect((new TiptapRenderer)->renderCached($page))->toBe('');
    });

    it('falls back to a fresh render when the cache store throws', function () {
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new RuntimeException('Redis connection refused'));

        $page = Page::factory()->create([
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Fallback text']]],
                ],
            ]),
        ]);

        $html = (new TiptapRenderer)->renderCached($page);

        expect($html)->toContain('Fallback text');
    });
});
