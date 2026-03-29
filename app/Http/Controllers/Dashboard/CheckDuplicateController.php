<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\UrlNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckDuplicateController extends Controller
{
    public function __construct(
        private readonly UrlNormalizer $urlNormalizer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['url' => ['required', 'string', 'url']]);

        $normalized = $this->urlNormalizer->normalize($request->input('url'));
        $base = $this->urlNormalizer->baseUrl($normalized);

        $exists = Link::withoutTrashed() // ::withTrashed() if you want to consider soft-deleted links as duplicates
            ->where('url', $normalized)
            ->exists();

        $similar = ! $exists && Link::withoutTrashed() // ::withTrashed() if you want to consider soft-deleted links as duplicates
            ->where('url', 'like', $base.'%')
            ->where('url', '!=', $normalized)
            ->exists();

        return response()->json(compact('exists', 'similar'));
    }
}
