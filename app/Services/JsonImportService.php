<?php

namespace App\Services;

use App\Services\Import\BucketMerger;
use App\Services\Import\ImportOptions;
use App\Services\Import\LinkImporter;
use App\Services\Import\TagMerger;

/**
 * Executes the second step of JSON import: merge-by-name buckets/tags, deduplicate links.
 *
 * @phpstan-type ImportResult array{imported: int, skipped: int, buckets_created: int, tags_created: int}
 */
class JsonImportService
{
    public function __construct(
        private readonly BucketMerger $bucketMerger,
        private readonly TagMerger $tagMerger,
        private readonly LinkImporter $linkImporter,
    ) {}

    /**
     * Import links, buckets, and tags from a parsed export array.
     *
     * @param  array<string>  $allowedBucketNames  Empty = all buckets in export
     * @param  array<string>  $allowedTagNames  Empty = all tags in export
     * @return ImportResult
     */
    public function import(array $data, array $allowedBucketNames = [], array $allowedTagNames = []): array
    {
        $options = $allowedBucketNames === [] && $allowedTagNames === []
            ? ImportOptions::all()
            : ImportOptions::selected($allowedBucketNames, $allowedTagNames);

        $bucketResult = $this->bucketMerger->merge($data['buckets'] ?? [], $options);
        $tagResult = $this->tagMerger->merge($data['tags'] ?? [], $options);
        $linkResult = $this->linkImporter->import($data['links'] ?? [], $bucketResult['map'], $tagResult['map'], $options);

        return [
            'imported' => $linkResult['imported'],
            'skipped' => $linkResult['skipped'],
            'buckets_created' => $bucketResult['created'],
            'tags_created' => $tagResult['created'],
        ];
    }
}
