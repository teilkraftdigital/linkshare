<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Collection;

class NetscapeExportService
{
    /**
     * Build a Netscape HTML bookmark file with a single root folder
     * containing direct links and nested sub-folders for child tags.
     *
     * @param  Collection<int, Link>  $rootLinks
     * @param  array<int, array{name: string, links: Collection}>  $children
     */
    public function buildHierarchical(string $rootName, Collection $rootLinks, array $children): string
    {
        $lines = [];
        $lines[] = '<!DOCTYPE NETSCAPE-Bookmark-file-1>';
        $lines[] = '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">';
        $lines[] = '<TITLE>Bookmarks</TITLE>';
        $lines[] = '<H1>Bookmarks</H1>';
        $lines[] = '<DL><p>';
        $lines[] = '    <DT><H2>'.e($rootName).'</H2>';
        $lines[] = '    <DL><p>';

        foreach ($rootLinks as $link) {
            $lines[] = $this->linkLine($link, 8);
        }

        foreach ($children as $child) {
            $lines[] = '        <DT><H3>'.e($child['name']).'</H3>';
            $lines[] = '        <DL><p>';

            foreach ($child['links'] as $link) {
                $lines[] = $this->linkLine($link, 12);
            }

            $lines[] = '        </DL><p>';
        }

        $lines[] = '    </DL><p>';
        $lines[] = '</DL><p>';

        return implode("\n", $lines)."\n";
    }

    /**
     * Build a Netscape HTML bookmark file.
     *
     * $folders is an array of named link groups:
     *   [['name' => 'php', 'links' => Collection<{url, title, description, created_at}>], ...]
     *
     * Links may appear in multiple folders (intentional for subtag support).
     *
     * @param  array<int, array{name: string, links: Collection}>  $folders
     */
    public function build(array $folders): string
    {
        $lines = [];
        $lines[] = '<!DOCTYPE NETSCAPE-Bookmark-file-1>';
        $lines[] = '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">';
        $lines[] = '<TITLE>Bookmarks</TITLE>';
        $lines[] = '<H1>Bookmarks</H1>';
        $lines[] = '<DL><p>';

        foreach ($folders as $folder) {
            $lines[] = '    <DT><H3>'.e($folder['name']).'</H3>';
            $lines[] = '    <DL><p>';

            foreach ($folder['links'] as $link) {
                $lines[] = $this->linkLine($link, 8);
            }

            $lines[] = '    </DL><p>';
        }
        $lines[] = '</DL><p>';

        return implode("\n", $lines)."\n";
    }

    /** @param  Link  $link */
    private function linkLine(mixed $link, int $indent): string
    {
        $pad = str_repeat(' ', $indent);
        $addDate = $link->created_at?->timestamp ?? 0;
        $line = $pad.'<DT><A HREF="'.e($link->url).'" ADD_DATE="'.$addDate.'">'.e($link->title).'</A>';

        if (! empty($link->description)) {
            $line .= "\n".$pad.'<DD>'.e($link->description);
        }

        return $line;
    }
}
