<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bucket;
use App\Models\Tag;
use App\Services\InboxBucketResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuickAddController extends Controller
{
    public function __construct(
        private readonly InboxBucketResolver $inboxBucketResolver,
    ) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('dashboard/QuickAdd', [
            'prefillUrl' => $request->query('url', ''),
            'prefillTitle' => $request->query('title', ''),
            'buckets' => Bucket::orderBy('is_inbox', 'desc')->orderBy('name')->get(['id', 'name', 'is_inbox']),
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'color', 'is_public']),
            'inboxBucketId' => $this->inboxBucketResolver->resolve()->id,
        ]);
    }
}
