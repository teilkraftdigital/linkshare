<?php

namespace App\Http\Controllers;

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
