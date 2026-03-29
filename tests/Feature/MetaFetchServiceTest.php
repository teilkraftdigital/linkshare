<?php

use App\Services\MetaFetchService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('returns title and description from html', function () {
    Http::fake(['*' => Http::response(
        '<html><head><title>Hello World</title><meta name="description" content="A description" /></head></html>',
    )]);

    $result = app(MetaFetchService::class)->fetch('https://example.com');

    expect($result['title'])->toBe('Hello World');
    expect($result['description'])->toBe('A description');
});

test('returns only title when description is missing', function () {
    Http::fake(['*' => Http::response(
        '<html><head><title>Only Title</title></head></html>',
    )]);

    $result = app(MetaFetchService::class)->fetch('https://example.com');

    expect($result['title'])->toBe('Only Title');
    expect($result['description'])->toBeNull();
});

test('reads og:description as fallback', function () {
    Http::fake(['*' => Http::response(
        '<html><head><title>Page</title><meta property="og:description" content="OG desc" /></head></html>',
    )]);

    $result = app(MetaFetchService::class)->fetch('https://example.com');

    expect($result['description'])->toBe('OG desc');
});

test('returns null values on connection failure', function () {
    Http::fake(['*' => fn () => throw new ConnectionException('timeout')]);

    $result = app(MetaFetchService::class)->fetch('https://unreachable.example.com');

    expect($result['title'])->toBeNull();
    expect($result['description'])->toBeNull();
});

test('returns null values on non-successful response', function () {
    Http::fake(['*' => Http::response('', 404)]);

    $result = app(MetaFetchService::class)->fetch('https://example.com/not-found');

    expect($result['title'])->toBeNull();
    expect($result['description'])->toBeNull();
});
