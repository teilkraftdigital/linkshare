<?php

use App\Services\UrlNormalizer;

beforeEach(function () {
    $this->normalizer = app(UrlNormalizer::class);
});

test('removes trailing slash from path', function () {
    expect($this->normalizer->normalize('https://example.com/'))->toBe('https://example.com');
    expect($this->normalizer->normalize('https://example.com/path/'))->toBe('https://example.com/path');
});

test('preserves query string', function () {
    expect($this->normalizer->normalize('https://example.com/path?q=1'))->toBe('https://example.com/path?q=1');
});

test('removes trailing slash before query string', function () {
    expect($this->normalizer->normalize('https://example.com/path/?q=1'))->toBe('https://example.com/path?q=1');
});

test('leaves url without trailing slash unchanged', function () {
    expect($this->normalizer->normalize('https://example.com/path'))->toBe('https://example.com/path');
});

test('baseUrl strips query string', function () {
    expect($this->normalizer->baseUrl('https://example.com/path?foo=bar'))->toBe('https://example.com/path');
});

test('baseUrl returns url unchanged when no query string', function () {
    expect($this->normalizer->baseUrl('https://example.com/path'))->toBe('https://example.com/path');
});
