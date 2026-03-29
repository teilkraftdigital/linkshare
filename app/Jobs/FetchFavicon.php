<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class FetchFavicon implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 15;

    public function __construct(public readonly Link $link, public readonly string $faviconUrl) {}

    public function uniqueId(): string
    {
        return (string) $this->link->id;
    }

    public function handle(): void
    {
        $link = $this->link->fresh();

        if (! $link) {
            return;
        }

        // Idempotent: skip if favicon already stored
        if ($link->getFirstMedia('favicon') !== null) {
            return;
        }

        try {
            $response = Http::timeout(10)->get($this->faviconUrl);

            if (! $response->successful()) {
                return;
            }

            $content = $response->body();

            // Skip suspiciously small responses (likely 404 pages served as 200)
            if (strlen($content) < 10) {
                return;
            }

            $extension = $this->guessExtension($response->header('Content-Type'), $this->faviconUrl);

            $media = $link->addMediaFromString($content)
                ->usingFileName("favicon.{$extension}")
                ->toMediaCollection('favicon');

            $link->update(['favicon_url' => $media->getUrl()]);
        } catch (ConnectionException) {
            // Silently skip — favicon is non-critical
        } catch (\Exception) {
            // Silently skip
        }
    }

    private function guessExtension(?string $contentType, string $url): string
    {
        $map = [
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/png' => 'png',
            'image/svg+xml' => 'svg',
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        ];

        if ($contentType) {
            $mime = strtolower(trim(explode(';', $contentType)[0]));

            if (isset($map[$mime])) {
                return $map[$mime];
            }
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['ico', 'png', 'svg', 'gif', 'jpg', 'jpeg', 'webp']) ? $ext : 'ico';
    }
}
