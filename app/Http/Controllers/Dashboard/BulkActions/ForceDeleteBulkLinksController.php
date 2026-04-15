<?php

namespace App\Http\Controllers\Dashboard\BulkActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BulkActions\ForceDeleteBulkLinksRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;

class ForceDeleteBulkLinksController extends Controller
{
    public function __invoke(ForceDeleteBulkLinksRequest $request): RedirectResponse
    {
        Link::onlyTrashed()
            ->whereIn('id', $request->validated()['link_ids'])
            ->forceDelete();

        return back();
    }
}
