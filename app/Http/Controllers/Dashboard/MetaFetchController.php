<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\MetaFetchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaFetchController extends Controller
{
    public function __construct(private readonly MetaFetchService $metaFetchService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['url' => ['required', 'url']]);

        $meta = $this->metaFetchService->fetch($request->string('url')->toString());

        return response()->json($meta);
    }
}
