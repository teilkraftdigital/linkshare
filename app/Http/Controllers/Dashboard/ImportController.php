<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bucket;
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
        return Inertia::render('dashboard/Import', [
            'buckets' => Bucket::orderBy('is_inbox', 'desc')->orderBy('name')->get(['id', 'name', 'is_inbox']),
            'inboxBucketId' => $this->inboxBucketResolver->resolve()->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:html,htm', 'max:5120'],
            'bucket_id' => ['required', 'integer', 'exists:buckets,id'],
        ]);

        $html = file_get_contents($request->file('file')->getRealPath());
        $result = $this->bookmarkImportService->import($html, (int) $request->input('bucket_id'));

        return back()->with('import_result', $result);
    }
}
