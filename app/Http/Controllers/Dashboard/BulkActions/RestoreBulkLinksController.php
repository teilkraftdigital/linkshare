<?php

namespace App\Http\Controllers\Dashboard\BulkActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BulkActions\RestoreBulkLinksRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;

class RestoreBulkLinksController extends Controller
{
    public function __invoke(RestoreBulkLinksRequest $request): RedirectResponse
    {
        Link::onlyTrashed()
            ->whereIn('id', $request->validated()['link_ids'])
            ->restore();

        return back();
    }
}
