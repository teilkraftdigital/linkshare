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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    public function __construct(
        private readonly InboxBucketResolver $inboxBucketResolver,
        private readonly LinkQueryBuilder $linkQueryBuilder,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['bucket_id', 'tag_id', 'search']);

        $links = $this->linkQueryBuilder->paginate($filters);

        return Inertia::render('dashboard/Links', [
            'links' => $links,
            'buckets' => Bucket::orderBy('is_inbox', 'desc')->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'inboxBucketId' => $this->inboxBucketResolver->resolve()->id,
            'filters' => $filters,
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

        // Store the external favicon URL immediately so the UI shows it right away,
        // before FetchFavicon has had a chance to download and replace it with a local path.
        if ($faviconUrl) {
            $validated['favicon_url'] = $faviconUrl;
        }

        $link = Link::create($validated);
        $link->tags()->sync($tagIds);

        FetchLinkMeta::dispatch($link);

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
}
