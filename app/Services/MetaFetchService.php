<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MetaFetchService
{
    /**
     * @return array{title: string|null, description: string|null}
     */
    public function fetch(string $url): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['Accept' => 'text/html'])
                ->get($url);

            if (! $response->successful()) {
                return ['title' => null, 'description' => null];
            }

            return $this->parse($response->body());
        } catch (ConnectionException) {
            return ['title' => null, 'description' => null];
        } catch (\Exception) {
            return ['title' => null, 'description' => null];
        }
    }

    /**
     * @return array{title: string|null, description: string|null}
     */
    private function parse(string $html): array
    {
        $doc = new \DOMDocument;

        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $title = $this->extractTitle($doc);
        $description = $this->extractDescription($doc);

        return ['title' => $title, 'description' => $description];
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
}
