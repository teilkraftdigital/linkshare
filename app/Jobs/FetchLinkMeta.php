<?php

namespace App\Jobs;

use App\Models\Link;
use App\Services\MetaFetchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchLinkMeta implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 15;

    public function __construct(public readonly Link $link) {}

    public function handle(MetaFetchService $metaFetchService): void
    {
        // Re-fetch the link to avoid acting on stale data
        $link = $this->link->fresh();

        if (! $link) {
            return;
        }

        $meta = $metaFetchService->fetch($link->url);

        $updates = [];

        // Use fetched title only when the import fell back to URL as title
        if ($meta['title'] && $link->title === $link->url) {
            $updates['title'] = $meta['title'];
        }

        if ($meta['description'] && ! $link->description) {
            $updates['description'] = $meta['description'];
        }

        if (! empty($updates)) {
            $link->update($updates);
        }
    }
}
