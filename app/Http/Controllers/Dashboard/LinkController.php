<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreLinkRequest;
use App\Http\Requests\Dashboard\UpdateLinkRequest;
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

        return Inertia::render('dashboard/Links', [
            'links' => $this->linkQueryBuilder->paginate($filters),
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
        unset($validated['tag_ids']);

        // Fall back to URL as title (same as import), job will replace it once meta loads
        $validated['title'] = ($validated['title'] ?? null) ?: $validated['url'];

        $link = Link::create($validated);
        $link->tags()->sync($tagIds);

        if ($link->title === $link->url) {
            FetchLinkMeta::dispatch($link);
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
