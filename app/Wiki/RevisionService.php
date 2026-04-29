<?php

namespace App\Wiki;

use App\Editor\TiptapExtractor;
use App\Models\Page;
use App\Models\PageRevision;
use Illuminate\Support\Collection;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\SequenceMatcher;

class RevisionService
{
    public function __construct(protected readonly TiptapExtractor $extractor)
    {
        //
    }

    /**
     * @return Collection<int, PageRevision>
     */
    public function getRevisions(Page $page): Collection
    {
        return $page->revisions()
            ->with('editor')
            ->orderBy('revision_number', 'desc')
            ->get();
    }

    public function getRevision(Page $page, int $number): PageRevision
    {
        $revision = $page->revisions()
            ->where('revision_number', $number)
            ->first();

        abort_if($revision === null, 404);

        return $revision;
    }

    /**
     * @return list<array{tag: string, line: string}>
     */
    public function diff(PageRevision $a, PageRevision $b): array
    {
        $oldText = $this->extractor->plainText($a->content ?? '');
        $newText = $this->extractor->plainText($b->content ?? '');

        $oldLines = $oldText !== '' ? explode("\n", $oldText) : [''];
        $newLines = $newText !== '' ? explode("\n", $newText) : [''];

        $differ = new Differ($oldLines, $newLines, ['ignoreLineEnding' => true]);

        $result = [];

        foreach ($differ->getGroupedOpcodes() as $hunk) {
            foreach ($hunk as [$tag, $i1, $i2, $j1, $j2]) {
                if ($tag === SequenceMatcher::OP_EQ) {
                    foreach (array_slice($oldLines, $i1, $i2 - $i1) as $line) {
                        $result[] = ['tag' => 'equal', 'line' => $line];
                    }
                } elseif ($tag === SequenceMatcher::OP_INS) {
                    foreach (array_slice($newLines, $j1, $j2 - $j1) as $line) {
                        $result[] = ['tag' => 'insert', 'line' => $line];
                    }
                } elseif ($tag === SequenceMatcher::OP_DEL) {
                    foreach (array_slice($oldLines, $i1, $i2 - $i1) as $line) {
                        $result[] = ['tag' => 'delete', 'line' => $line];
                    }
                } elseif ($tag === SequenceMatcher::OP_REP) {
                    foreach (array_slice($oldLines, $i1, $i2 - $i1) as $line) {
                        $result[] = ['tag' => 'delete', 'line' => $line];
                    }
                    foreach (array_slice($newLines, $j1, $j2 - $j1) as $line) {
                        $result[] = ['tag' => 'insert', 'line' => $line];
                    }
                }
            }
        }

        return $result;
    }
}
