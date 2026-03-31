<?php

namespace App\Services;

use Illuminate\Support\Collection;

class NetscapeExportService
{
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
                $addDate = $link->created_at?->timestamp ?? 0;
                $lines[] = '        <DT><A HREF="'.e($link->url).'" ADD_DATE="'.$addDate.'">'.e($link->title).'</A>';

                if (! empty($link->description)) {
                    $lines[] = '        <DD>'.e($link->description);
                }
            }

            $lines[] = '    </DL><p>';
        }

        $lines[] = '</DL><p>';

        return implode("\n", $lines)."\n";
    }
}
