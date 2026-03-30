<?php

namespace App\Services;

use App\Jobs\FetchLinkMeta;
use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;

/**
 * Executes the second step of JSON import: merge-by-name buckets/tags, deduplicate links.
 *
 * @phpstan-type BucketData array{name: string, color: string, is_inbox: bool}
 * @phpstan-type TagData array{name: string, slug: string, color: string, description: string|null, is_public: bool}
 * @phpstan-type LinkData array{url: string, title: string, description: string|null, notes: string|null, bucket: string, tags: list<string>}
 * @phpstan-type ImportResult array{imported: int, skipped: int, buckets_created: int, tags_created: int}
 */
class JsonImportService
{
    public function __construct(
        private readonly UrlNormalizer $urlNormalizer,
        private readonly SlugGenerator $slugGenerator,
        private readonly InboxBucketResolver $inboxBucketResolver,
    ) {}

    /**
     * Import links, buckets, and tags from a parsed export array.
     *
     * @param  array<string>  $allowedBucketNames  Empty = all buckets in export
     * @param  array<string>  $allowedTagNames  Empty = all tags in export
     * @return ImportResult
     */
    public function import(array $data, array $allowedBucketNames, array $allowedTagNames): array
    {
        $allBuckets = empty($allowedBucketNames);
        $allTags = empty($allowedTagNames);

        // Normalize name sets for case-insensitive comparison
        $allowedBucketLower = array_map('mb_strtolower', $allowedBucketNames);
        $allowedTagLower = array_map('mb_strtolower', $allowedTagNames);

        // --- Import buckets ---
        $bucketsCreated = 0;
        /** @var array<string, Bucket> $bucketMap name-lowercase → model */
        $bucketMap = [];

        foreach (($data['buckets'] ?? []) as $b) {
            $name = $b['name'] ?? '';
            if ($name === '') {
                continue;
            }

            $nameLower = mb_strtolower($name);
            if (! $allBuckets && ! in_array($nameLower, $allowedBucketLower, true)) {
                continue;
            }

            $existing = Bucket::whereRaw('LOWER(name) = ?', [$nameLower])->first();

            if ($existing) {
                $bucketMap[$nameLower] = $existing;
            } else {
                $bucket = Bucket::create([
                    'name' => $name,
                    'color' => $b['color'] ?? 'gray',
                    'is_inbox' => (bool) ($b['is_inbox'] ?? false),
                ]);
                $bucketMap[$nameLower] = $bucket;
                $bucketsCreated++;
            }
        }

        // --- Import tags ---
        $tagsCreated = 0;
        /** @var array<string, Tag> $tagMap name-lowercase → model */
        $tagMap = [];

        foreach (($data['tags'] ?? []) as $t) {
            $name = $t['name'] ?? '';
            if ($name === '') {
                continue;
            }

            $nameLower = mb_strtolower($name);
            if (! $allTags && ! in_array($nameLower, $allowedTagLower, true)) {
                continue;
            }

            $existing = Tag::whereRaw('LOWER(name) = ?', [$nameLower])->withTrashed()->first();

            if ($existing) {
                $tagMap[$nameLower] = $existing;
            } else {
                $tag = Tag::create([
                    'name' => $name,
                    'slug' => $this->slugGenerator->generate($name),
                    'color' => $t['color'] ?? 'gray',
                    'description' => $t['description'] ?? null,
                    'is_public' => (bool) ($t['is_public'] ?? false),
                ]);
                $tagMap[$nameLower] = $tag;
                $tagsCreated++;
            }
        }

        // --- Import links ---
        $imported = 0;
        $skipped = 0;
        $seenNormalized = [];

        $existingUrls = Link::withTrashed()
            ->pluck('url')
            ->map(fn (string $url) => $this->urlNormalizer->normalize($url))
            ->flip()
            ->all();

        $inbox = $this->inboxBucketResolver->resolve();

        foreach (($data['links'] ?? []) as $l) {
            $rawUrl = $l['url'] ?? '';
            if ($rawUrl === '') {
                $skipped++;

                continue;
            }

            // Resolve bucket; skip link if bucket was excluded or doesn't exist in import
            $bucketNameLower = mb_strtolower($l['bucket'] ?? '');
            if (! isset($bucketMap[$bucketNameLower])) {
                // Bucket was excluded or not in export — put in inbox or skip
                if (! $allBuckets) {
                    $skipped++;

                    continue;
                }
                $bucket = $inbox;
            } else {
                $bucket = $bucketMap[$bucketNameLower];
            }

            $normalized = $this->urlNormalizer->normalize($rawUrl);

            // Skip within-file duplicates
            if (isset($seenNormalized[$normalized])) {
                $skipped++;

                continue;
            }

            $seenNormalized[$normalized] = true;

            // Skip if already exists in DB
            if (isset($existingUrls[$normalized])) {
                $skipped++;

                continue;
            }

            $link = Link::create([
                'url' => $normalized,
                'title' => $l['title'] ?? $normalized,
                'description' => $l['description'] ?? null,
                'notes' => $l['notes'] ?? null,
                'bucket_id' => $bucket->id,
            ]);

            // Attach tags that are in the allowed set
            $tagIds = [];
            foreach (($l['tags'] ?? []) as $tagName) {
                $tagNameLower = mb_strtolower($tagName);
                if (isset($tagMap[$tagNameLower])) {
                    $tagIds[] = $tagMap[$tagNameLower]->id;
                }
            }

            if ($tagIds) {
                $link->tags()->sync($tagIds);
            }

            FetchLinkMeta::dispatch($link);

            $existingUrls[$normalized] = true;
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'buckets_created' => $bucketsCreated,
            'tags_created' => $tagsCreated,
        ];
    }
}
