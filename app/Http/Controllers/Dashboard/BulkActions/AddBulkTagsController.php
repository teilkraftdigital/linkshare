<?php

namespace App\Http\Controllers\Dashboard\BulkActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BulkActions\AddBulkTagsRequest;
use App\Models\Link;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;

class AddBulkTagsController extends Controller
{
    public function __invoke(AddBulkTagsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Link::whereIn('id', $validated['link_ids'])
            ->each(fn (Link $link) => $link->tags()->syncWithoutDetaching($validated['tag_ids']));

        Tag::whereIn('id', $validated['tag_ids'])->each(fn (Tag $tag) => $tag->touch());

        return back();
    }
}
