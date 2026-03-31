<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Tag;
use App\Services\NetscapeExportService;
use Illuminate\Http\Response as HttpResponse;
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

    public function export(Tag $tag, NetscapeExportService $exporter): HttpResponse
    {
        abort_unless($tag->is_public, 404);

        $links = $tag->links()
            ->select(['url', 'title', 'description', 'created_at'])
            ->orderBy('title')
            ->get();

        $html = $exporter->build([
            ['name' => $tag->name, 'links' => $links],
        ]);

        $filename = 'bookmarks_linkshare_'.now()->toDateString().'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
