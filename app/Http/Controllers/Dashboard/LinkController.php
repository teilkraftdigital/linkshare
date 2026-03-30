<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreLinkRequest;
use App\Http\Requests\Dashboard\UpdateLinkRequest;
use App\Jobs\FetchFavicon;
use App\Jobs\FetchLinkMeta;
use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Services\InboxBucketResolver;
use App\Services\LinkQueryBuilder;
use App\Services\MetaFetchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    public function __construct(
        private readonly InboxBucketResolver $inboxBucketResolver,
        private readonly LinkQueryBuilder $linkQueryBuilder,
        private readonly MetaFetchService $metaFetchService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['bucket_id', 'tag_id', 'search', 'trashed']);

        $links = $this->linkQueryBuilder->paginate($filters);
        $links->through(fn (Link $link) => array_merge($link->toArray(), [
            'favicon_url' => $link->getFirstMediaUrl('favicon') ?: null,
        ]));

        return Inertia::render('dashboard/Links', [
            'links' => $links,
            'buckets' => Bucket::orderBy('is_inbox', 'desc')->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'inboxBucketId' => $this->inboxBucketResolver->resolve()->id,
            'filters' => $filters,
            'showTrashed' => $request->boolean('trashed'),
        ]);
    }

    public function store(StoreLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        $faviconUrl = $validated['favicon_url'] ?? null;
        unset($validated['tag_ids'], $validated['favicon_url']);

        // Fall back to URL as title (same as import), job will replace it once meta loads
        $validated['title'] = ($validated['title'] ?? null) ?: $validated['url'];

        $link = Link::create($validated);
        $link->tags()->sync($tagIds);

        // Always enrich title/description in background
        FetchLinkMeta::dispatch($link);

        // If the frontend already resolved the favicon URL during interactive meta fetch,
        // dispatch FetchFavicon directly — avoids re-fetching the full HTML in the job.
        // ShouldBeUnique on FetchFavicon prevents a second dispatch from FetchLinkMeta.
        if ($faviconUrl) {
            FetchFavicon::dispatch($link, $faviconUrl);
        }

        return back();
    }

    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $link->update($validated);
        $link->tags()->sync($tagIds);

        return back();
    }

    public function destroy(Link $link): RedirectResponse
    {
        $link->delete();

        return back();
    }

    public function refetchMeta(Link $link): RedirectResponse
    {
        $meta = $this->metaFetchService->fetch($link->url);

        $updates = [];

        if ($meta['title']) {
            $updates['title'] = $meta['title'];
        }

        if ($meta['description']) {
            $updates['description'] = $meta['description'];
        }

        if (! empty($updates)) {
            $link->update($updates);
        }

        if ($meta['favicon_url']) {
            $link->clearMediaCollection('favicon');
            FetchFavicon::dispatchSync($link, $meta['favicon_url']);
        }

        $metaFound = $meta['title'] || $meta['description'] || $meta['favicon_url'];

        return back()->with(
            $metaFound ? 'refetch_success' : 'refetch_failed',
            true,
        );
    }

    public function restore(Link $link): RedirectResponse
    {
        $link->restore();

        return back();
    }

    public function forceDelete(Link $link): RedirectResponse
    {
        $link->forceDelete();

        return back();
    }
}
