<?php

namespace App\Services\Import;

use App\Jobs\FetchLinkMeta;
use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Services\InboxBucketResolver;
use App\Services\UrlNormalizer;

final class LinkImporter
{
    public function __construct(
        private readonly UrlNormalizer $urlNormalizer,
        private readonly InboxBucketResolver $inboxBucketResolver,
    ) {}

    /**
     * Deduplicate and create links, attach tags, dispatch meta jobs.
     *
     * @param  list<array{url: string, title: string, description: string|null, notes: string|null, bucket: string, tags: list<string>}>  $linkData
     * @param  array<string, Bucket>  $bucketMap  name-lowercase → model
     * @param  array<string, Tag>  $tagMap  name-lowercase → model
     * @return array{imported: int, skipped: int}
     */
    public function import(array $linkData, array $bucketMap, array $tagMap, ImportOptions $options): array
    {
        $imported = 0;
        $skipped = 0;
        $seenNormalized = [];

        $existingUrls = Link::query()
            ->pluck('url')
            ->map(fn (string $url) => $this->urlNormalizer->normalize($url))
            ->flip()
            ->all();

        $inbox = $this->inboxBucketResolver->resolve();

        foreach ($linkData as $l) {
            $rawUrl = $l['url'] ?? '';

            if ($rawUrl === '') {
                $skipped++;

                continue;
            }

            $bucketNameLower = mb_strtolower($l['bucket'] ?? '');

            if (! isset($bucketMap[$bucketNameLower])) {
                if ($options->isSelectiveOnBuckets()) {
                    $skipped++;

                    continue;
                }
                $bucket = $inbox;
            } else {
                $bucket = $bucketMap[$bucketNameLower];
            }

            $normalized = $this->urlNormalizer->normalize($rawUrl);

            if (isset($seenNormalized[$normalized]) || isset($existingUrls[$normalized])) {
                $skipped++;

                continue;
            }

            $seenNormalized[$normalized] = true;

            $link = Link::create([
                'url' => $normalized,
                'title' => $l['title'] ?? $normalized,
                'description' => $l['description'] ?? null,
                'notes' => $l['notes'] ?? null,
                'bucket_id' => $bucket->id,
            ]);

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

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
