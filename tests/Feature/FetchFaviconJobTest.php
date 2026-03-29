<?php

use App\Jobs\FetchFavicon;
use App\Models\Bucket;
use App\Models\Link;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $inbox = Bucket::factory()->inbox()->create();
    $this->link = Link::factory()->create([
        'url' => 'https://example.com',
        'bucket_id' => $inbox->id,
    ]);
});

test('favicon is downloaded and stored', function () {
    Http::fake([
        'https://example.com/favicon.ico' => Http::response(str_repeat('X', 100), 200, ['Content-Type' => 'image/x-icon']),
    ]);

    (new FetchFavicon($this->link, 'https://example.com/favicon.ico'))->handle();

    expect($this->link->fresh()->getFirstMedia('favicon'))->not->toBeNull();
});

test('favicon is not re-downloaded when already stored', function () {
    Http::fake([
        'https://example.com/favicon.ico' => Http::response(str_repeat('X', 100), 200, ['Content-Type' => 'image/x-icon']),
    ]);

    // Store it once
    (new FetchFavicon($this->link, 'https://example.com/favicon.ico'))->handle();

    // Reset fakes to verify no new request is sent
    Http::fake([
        'https://example.com/favicon.ico' => Http::response(str_repeat('X', 100), 200, ['Content-Type' => 'image/x-icon']),
    ]);

    // Run again — should be idempotent
    (new FetchFavicon($this->link, 'https://example.com/favicon.ico'))->handle();

    Http::assertNothingSent();
});

test('job silently skips on non-successful response', function () {
    Http::fake([
        'https://example.com/favicon.ico' => Http::response('', 404),
    ]);

    (new FetchFavicon($this->link, 'https://example.com/favicon.ico'))->handle();

    expect($this->link->fresh()->getFirstMedia('favicon'))->toBeNull();
});
