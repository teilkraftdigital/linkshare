<?php

namespace App\Services;

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;

/**
 * @phpstan-type ExportBucket array{name: string, color: string, is_inbox: bool}
 * @phpstan-type ExportTag array{name: string, slug: string, color: string, description: string|null, is_public: bool}
 * @phpstan-type ExportLink array{url: string, title: string, description: string|null, notes: string|null, bucket: string, tags: list<string>}
 * @phpstan-type ExportPayload array{version: int, exported_at: string, includes_notes: bool, buckets: list<ExportBucket>, tags: list<ExportTag>, links: list<ExportLink>}
 */
class ExportService
{
    /**
     * Build a selective export payload.
     *
     * @param  list<int>  $bucketIds  Only export these buckets (and their links). Empty = all.
     * @param  list<int>  $tagIds  Only export these tags. Empty = all.
     * @return ExportPayload
     */
    public function build(array $bucketIds = [], array $tagIds = [], bool $includesNotes = false): array
    {
        $allBuckets = empty($bucketIds);
        $allTags = empty($tagIds);

        $buckets = Bucket::orderBy('is_inbox', 'desc')
            ->orderBy('name')
            ->when(! $allBuckets, fn ($q) => $q->whereIn('id', $bucketIds))
            ->get();

        $tags = Tag::orderBy('name')
            ->when(! $allTags, fn ($q) => $q->whereIn('id', $tagIds))
            ->get();

        $allowedTagIds = $tags->pluck('id')->all();

        $links = Link::with(['bucket', 'tags'])
            ->when(! $allBuckets, fn ($q) => $q->whereIn('bucket_id', $bucketIds))
            ->orderBy('created_at')
            ->get();

        return [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'includes_notes' => $includesNotes,
            'buckets' => $buckets->map(fn (Bucket $bucket) => [
                'name' => $bucket->name,
                'color' => $bucket->color,
                'is_inbox' => (bool) $bucket->is_inbox,
            ])->values()->all(),
            'tags' => $tags->map(fn (Tag $tag) => [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
                'description' => $tag->description,
                'is_public' => (bool) $tag->is_public,
            ])->values()->all(),
            'links' => $links->map(fn (Link $link) => [
                'url' => $link->url,
                'title' => $link->title,
                'description' => $link->description,
                'notes' => $includesNotes ? $link->notes : null,
                'bucket' => $link->bucket->name,
                'tags' => $link->tags
                    ->when(! $allTags, fn ($c) => $c->filter(fn (Tag $tag) => in_array($tag->id, $allowedTagIds)))
                    ->pluck('name')
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];
    }
}
