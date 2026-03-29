<?php

namespace App\Services;

use App\Models\Link;

/**
 * Parses and imports Netscape Bookmark HTML files (exported from Chrome, Firefox, Safari).
 *
 * @phpstan-type ParsedBookmark array{url: string, title: string}
 * @phpstan-type ImportResult array{imported: int, skipped: int, hints: int}
 */
class BookmarkImportService
{
    public function __construct(
        private readonly UrlNormalizer $urlNormalizer,
    ) {}

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

    /**
     * Import bookmarks from HTML into the given bucket, with deduplication.
     *
     * - Exact URL matches (normalized) are skipped.
     * - Within-file duplicates are skipped after the first occurrence.
     * - URLs with the same base but different query strings are imported and flagged as hints.
     *
     * @return ImportResult
     */
    public function import(string $html, int $bucketId): array
    {
        $bookmarks = $this->parse($html);

        $imported = 0;
        $skipped = 0;
        $hints = 0;

        // Track normalized URLs seen so far in this import (for within-file dedup)
        $seenNormalized = [];

        // Load all existing normalized URLs from DB for fast lookup
        $existingUrls = Link::withTrashed()
            ->pluck('url')
            ->map(fn (string $url) => $this->urlNormalizer->normalize($url))
            ->flip()
            ->all();

        foreach ($bookmarks as $bookmark) {
            $normalized = $this->urlNormalizer->normalize($bookmark['url']);

            // Skip within-file duplicates
            if (isset($seenNormalized[$normalized])) {
                $skipped++;

                continue;
            }

            $seenNormalized[$normalized] = true;

            // Skip if exact URL already exists in DB
            if (isset($existingUrls[$normalized])) {
                $skipped++;

                continue;
            }

            // Check for similar URL (same base, different query string)
            $base = $this->urlNormalizer->baseUrl($normalized);
            $hasSimilar = collect($existingUrls)->keys()->contains(
                fn (string $existingUrl) => $this->urlNormalizer->baseUrl($existingUrl) === $base
                    && $existingUrl !== $normalized
            );

            if ($hasSimilar) {
                $hints++;
            }

            Link::create([
                'url' => $normalized,
                'title' => $bookmark['title'],
                'bucket_id' => $bucketId,
            ]);

            // Add to existing set so within-file entries don't trigger false similar-checks
            $existingUrls[$normalized] = true;
            $imported++;
        }

        return compact('imported', 'skipped', 'hints');
    }
}
