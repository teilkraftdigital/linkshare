<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreBucketRequest;
use App\Http\Requests\Dashboard\UpdateBucketRequest;
use App\Models\Bucket;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BucketController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/Buckets', [
            'buckets' => Bucket::orderBy('is_inbox', 'desc')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBucketRequest $request): RedirectResponse
    {
        Bucket::create($request->validated());

        return back();
    }

    public function update(UpdateBucketRequest $request, Bucket $bucket): RedirectResponse
    {
        $bucket->update($request->validated());

        return back();
    }

    public function destroy(Bucket $bucket): RedirectResponse
    {
        abort_if($bucket->is_inbox, 403, 'The inbox bucket cannot be deleted.');

        // When a Link model exists, move all links to the inbox before deleting:
        // $inbox = $inboxBucketResolver->resolve();
        // $bucket->links()->update(['bucket_id' => $inbox->id]);

        $bucket->delete();

        return back();
    }
}
