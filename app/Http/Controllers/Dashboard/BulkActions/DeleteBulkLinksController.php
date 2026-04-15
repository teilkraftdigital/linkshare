<?php

namespace App\Http\Controllers\Dashboard\BulkActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BulkActions\DeleteBulkLinksRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;

class DeleteBulkLinksController extends Controller
{
    public function __invoke(DeleteBulkLinksRequest $request): RedirectResponse
    {
        Link::whereIn('id', $request->validated()['link_ids'])->delete();

        return back();
    }
}
