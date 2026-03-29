<?php

namespace App\Http\Controllers;

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

        return Inertia::render('tags/Show', [
            'tag' => $tag->only('name', 'description', 'slug'),
            'links' => $links,
        ]);
    }
}
