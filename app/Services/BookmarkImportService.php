<?php

namespace App\Services;

/**
 * Parses Netscape Bookmark HTML files (exported from Chrome, Firefox, Safari).
 *
 * @phpstan-type ParsedBookmark array{url: string, title: string}
 */
class BookmarkImportService
{
    /**
     * Parse bookmark links from a Netscape HTML bookmark file.
     *
     * @return list<ParsedBookmark>
     */
    public function parse(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $dom = new \DOMDocument;

        // Suppress warnings from quirky bookmark HTML structure
        @$dom->loadHTML($html, LIBXML_NOERROR);

        $anchors = $dom->getElementsByTagName('a');
        $bookmarks = [];

        /** @var \DOMElement $anchor */
        foreach ($anchors as $anchor) {
            $url = trim($anchor->getAttribute('href'));
            $title = trim($anchor->textContent);

            if ($url === '' || ! str_starts_with($url, 'http')) {
                continue;
            }

            $bookmarks[] = [
                'url' => $url,
                'title' => $title !== '' ? $title : $url,
            ];
        }

        return $bookmarks;
    }
}
