<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function __invoke(ExportService $exportService): Response
    {
        $payload = $exportService->build();
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
