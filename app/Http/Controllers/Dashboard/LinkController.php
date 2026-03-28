<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreLinkRequest;
use App\Http\Requests\Dashboard\UpdateLinkRequest;
use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Services\InboxBucketResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    public function __construct(private readonly InboxBucketResolver $inboxBucketResolver) {}

    public function index(): Response
    {
        return Inertia::render('dashboard/Links', [
            'links' => Link::with(['bucket', 'tags'])->orderByDesc('id')->get(),
            'buckets' => Bucket::orderBy('is_inbox', 'desc')->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'inboxBucketId' => $this->inboxBucketResolver->resolve()->id,
        ]);
    }

    public function store(StoreLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $link = Link::create($validated);
        $link->tags()->sync($tagIds);

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
