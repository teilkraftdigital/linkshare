<?php

namespace App\Http\Controllers\Dashboard\BulkActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BulkActions\RemoveBulkTagsRequest;
use App\Models\Link;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;

class RemoveBulkTagsController extends Controller
{
    public function __invoke(RemoveBulkTagsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Link::whereIn('id', $validated['link_ids'])
            ->each(fn (Link $link) => $link->tags()->detach($validated['tag_ids']));

        Tag::whereIn('id', $validated['tag_ids'])->each(fn (Tag $tag) => $tag->touch());

        return back();
    }
}
