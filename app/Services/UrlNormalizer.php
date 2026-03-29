<?php

namespace App\Services;

class UrlNormalizer
{
    /**
     * Normalize a URL: assume https when no protocol given, remove trailing slash from the path.
     *
     * Examples:
     *   https://example.com/        → https://example.com
     *   https://example.com/path/   → https://example.com/path
     *   https://example.com/path/?q=1 → https://example.com/path?q=1
     *   www.example.com             → https://www.example.com
     */
    public function normalize(string $url): string
    {
        if (! str_contains($url, '://')) {
            $url = 'https://'.$url;
        }

        $parsed = parse_url($url);

        if ($parsed === false) {
            return $url;
        }

        $path = rtrim($parsed['path'] ?? '', '/');
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        $scheme = ($parsed['scheme'] ?? 'https').'://';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        return $scheme.$host.$port.$path.$query.$fragment;
    }

    /**
     * Return the URL without its query string, for similarity comparison.
     *
     * Examples:
     *   https://example.com/path?foo=bar → https://example.com/path
     *   https://example.com/path         → https://example.com/path
     */
    public function baseUrl(string $url): string
    {
        $normalized = $this->normalize($url);
        $pos = strpos($normalized, '?');

        return $pos !== false ? substr($normalized, 0, $pos) : $normalized;
    }
}
