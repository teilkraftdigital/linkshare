<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function show(Tag $tag): Response
    {
        abort_unless($tag->is_public, 404);

        $links = $tag->links()
            ->select(['id', 'url', 'title', 'description'])
            ->orderBy('title')
            ->get();

        $links->each(fn (Link $link) => $link->setAttribute('favicon_url', $link->getFirstMediaUrl('favicon') ?: null));

        return Inertia::render('tags/Show', [
            'tag' => $tag->only('name', 'description', 'slug'),
            'links' => $links,
        ]);
    }
}
