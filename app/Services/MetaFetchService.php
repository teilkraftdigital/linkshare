<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MetaFetchService
{
    /**
     * @return array{title: string|null, description: string|null, favicon_url: string|null}
     */
    public function fetch(string $url): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['Accept' => 'text/html'])
                ->get($url);

            if (! $response->successful()) {
                return ['title' => null, 'description' => null, 'favicon_url' => null];
            }

            return $this->parse($response->body(), $url);
        } catch (ConnectionException) {
            return ['title' => null, 'description' => null, 'favicon_url' => null];
        } catch (\Exception) {
            return ['title' => null, 'description' => null, 'favicon_url' => null];
        }
    }

    /**
     * @return array{title: string|null, description: string|null, favicon_url: string|null}
     */
    private function parse(string $html, string $baseUrl): array
    {
        $doc = new \DOMDocument;

        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $title = $this->extractTitle($doc);
        $description = $this->extractDescription($doc);
        $faviconUrl = $this->extractFaviconUrl($doc, $baseUrl);

        return ['title' => $title, 'description' => $description, 'favicon_url' => $faviconUrl];
    }

    private function extractTitle(\DOMDocument $doc): ?string
    {
        $nodes = $doc->getElementsByTagName('title');

        if ($nodes->length === 0) {
            return null;
        }

        $text = trim($nodes->item(0)->textContent);

        return $text !== '' ? $text : null;
    }

    private function extractDescription(\DOMDocument $doc): ?string
    {
        foreach ($doc->getElementsByTagName('meta') as $meta) {
            $name = strtolower((string) $meta->getAttribute('name'));
            $property = strtolower((string) $meta->getAttribute('property'));

            if ($name === 'description' || $property === 'og:description') {
                $content = trim($meta->getAttribute('content'));

                if ($content !== '') {
                    return $content;
                }
            }
        }

        return null;
    }

    private function extractFaviconUrl(\DOMDocument $doc, string $baseUrl): ?string
    {
        $parsed = parse_url($baseUrl);
        $origin = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');

        foreach ($doc->getElementsByTagName('link') as $link) {
            $rel = strtolower((string) $link->getAttribute('rel'));

            if (str_contains($rel, 'icon')) {
                $href = trim($link->getAttribute('href'));

                if ($href !== '') {
                    // Resolve relative URLs
                    if (str_starts_with($href, '//')) {
                        return ($parsed['scheme'] ?? 'https').':'.$href;
                    }

                    if (str_starts_with($href, '/')) {
                        return $origin.$href;
                    }

                    if (str_contains($href, '://')) {
                        return $href;
                    }
                }
            }
        }

        // Fallback to /favicon.ico
        return $origin.'/favicon.ico';
    }
}
