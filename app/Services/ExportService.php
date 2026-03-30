<?php

namespace App\Services;

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;

/**
 * @phpstan-type ExportBucket array{name: string, color: string, is_inbox: bool}
 * @phpstan-type ExportTag array{name: string, slug: string, color: string, description: string|null, is_public: bool}
 * @phpstan-type ExportLink array{url: string, title: string, description: string|null, notes: null, bucket: string, tags: list<string>}
 * @phpstan-type ExportPayload array{version: int, exported_at: string, includes_notes: bool, buckets: list<ExportBucket>, tags: list<ExportTag>, links: list<ExportLink>}
 */
class ExportService
{
    /**
     * Build the full export payload (all active, non-soft-deleted data).
     *
     * @return ExportPayload
     */
    public function build(): array
    {
        $buckets = Bucket::orderBy('is_inbox', 'desc')->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $links = Link::with(['bucket', 'tags'])->orderBy('created_at')->get();

        return [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'includes_notes' => false,
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
                'notes' => null,
                'bucket' => $link->bucket->name,
                'tags' => $link->tags->pluck('name')->values()->all(),
            ])->values()->all(),
        ];
    }
}
