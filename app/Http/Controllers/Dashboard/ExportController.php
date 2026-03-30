<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function __invoke(Request $request, ExportService $exportService): Response
    {
        $validated = $request->validate([
            'bucket_ids' => ['nullable', 'array'],
            'bucket_ids.*' => ['integer', 'exists:buckets,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'includes_notes' => ['nullable', 'boolean'],
        ]);

        $payload = $exportService->build(
            bucketIds: $validated['bucket_ids'] ?? [],
            tagIds: $validated['tag_ids'] ?? [],
            includesNotes: (bool) ($validated['includes_notes'] ?? false),
        );

        $filename = 'linkshare-export-'.now()->toDateString().'.json';

        return response(
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ],
        );
    }
}
