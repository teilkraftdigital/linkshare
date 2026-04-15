<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Tag;
use App\Services\NetscapeExportService;
use Illuminate\Http\Response as HttpResponse;

class TagExportController extends Controller
{
    public function __invoke(Tag $tag, NetscapeExportService $exporter): HttpResponse
    {
        abort_unless($tag->is_public, 404);

        $links = $tag->links()
            ->select(['url', 'title', 'description', 'created_at'])
            ->orderBy('title')
            ->get();

        $children = $tag->children()
            ->get()
            ->map(function (Tag $child) {
                $childLinks = $child->links()->select(['id', 'url', 'title', 'description'])->orderBy('title')->get();
                $childLinks->each(fn (Link $link) => $link->setAttribute('favicon_url', $link->getFirstMediaUrl('favicon') ?: null));

                return [
                    ...$child->only('id', 'name', 'slug', 'description'),
                    'links' => $childLinks,
                ];
            })
            ->filter(fn ($child) => count($child['links']) > 0)
            ->sortBy('name')
            ->values();

        $html = $exporter->build(
            [
                ['name' => $tag->name, 'links' => $links],
                ...$children->map(fn ($child) => ['name' => $child['name'], 'links' => $child['links']])->all(),
            ],
        );

        $filename = 'bookmarks_linkshare_'.now()->toDateString().'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
