<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\BookmarkImportService;
use App\Services\InboxBucketResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function __construct(
        private readonly InboxBucketResolver $inboxBucketResolver,
        private readonly BookmarkImportService $bookmarkImportService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('dashboard/Import');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:html,htm', 'max:5120'],
        ]);

        $html = file_get_contents($request->file('file')->getRealPath());
        $bookmarks = $this->bookmarkImportService->parse($html);

        $inbox = $this->inboxBucketResolver->resolve();
        $count = 0;

        foreach ($bookmarks as $bookmark) {
            Link::create([
                'url' => $bookmark['url'],
                'title' => $bookmark['title'],
                'bucket_id' => $inbox->id,
            ]);
            $count++;
        }

        return back()->with('import_count', $count);
    }
}
