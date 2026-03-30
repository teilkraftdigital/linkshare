<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JsonImportController extends Controller
{
    /**
     * Parse an uploaded JSON export file and return a preview.
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:json', 'max:10240'],
        ]);

        $contents = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            return response()->json([
                'error' => 'Ungültige JSON-Datei.',
            ], 422);
        }

        if (($data['version'] ?? null) !== 1) {
            return response()->json([
                'error' => 'Nicht unterstütztes Export-Format (version ≠ 1).',
            ], 422);
        }

        $buckets = collect($data['buckets'] ?? [])->map(fn (array $b) => [
            'name' => $b['name'] ?? '',
            'color' => $b['color'] ?? 'gray',
            'is_inbox' => (bool) ($b['is_inbox'] ?? false),
        ])->filter(fn (array $b) => $b['name'] !== '')->values();

        $tags = collect($data['tags'] ?? [])->map(fn (array $t) => [
            'name' => $t['name'] ?? '',
            'color' => $t['color'] ?? 'gray',
        ])->filter(fn (array $t) => $t['name'] !== '')->values();

        $linkCount = count($data['links'] ?? []);

        return response()->json([
            'buckets' => $buckets,
            'tags' => $tags,
            'link_count' => $linkCount,
        ]);
    }
}
