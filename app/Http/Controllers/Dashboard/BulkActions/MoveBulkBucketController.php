<?php

namespace App\Http\Controllers\Dashboard\BulkActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BulkActions\MoveBulkBucketRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;

class MoveBulkBucketController extends Controller
{
    public function __invoke(MoveBulkBucketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Link::whereIn('id', $validated['link_ids'])
            ->update(['bucket_id' => $validated['bucket_id']]);

        return back();
    }
}
