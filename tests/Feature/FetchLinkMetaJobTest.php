<?php

use App\Jobs\FetchLinkMeta;
use App\Models\Bucket;
use App\Models\Link;
use App\Services\MetaFetchService;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('updates description when link has none', function () {
    $link = Link::factory()->create([
        'url' => 'https://example.com',
        'title' => 'Example',
        'description' => null,
        'bucket_id' => $this->inbox->id,
    ]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => 'Example Page', 'description' => 'A great page', 'favicon_url' => null]);

    (new FetchLinkMeta($link))->handle(app(MetaFetchService::class));

    expect($link->fresh()->description)->toBe('A great page');
    expect($link->fresh()->title)->toBe('Example'); // title unchanged — was already set
});

test('updates title when it equals the url', function () {
    $link = Link::factory()->create([
        'url' => 'https://example.com',
        'title' => 'https://example.com', // fallback title
        'description' => null,
        'bucket_id' => $this->inbox->id,
    ]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => 'Example Page', 'description' => null, 'favicon_url' => null]);

    (new FetchLinkMeta($link))->handle(app(MetaFetchService::class));

    expect($link->fresh()->title)->toBe('Example Page');
});

test('does not overwrite existing description', function () {
    $link = Link::factory()->create([
        'url' => 'https://example.com',
        'title' => 'Example',
        'description' => 'My own note',
        'bucket_id' => $this->inbox->id,
    ]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => 'Example Page', 'description' => 'Fetched description', 'favicon_url' => null]);

    (new FetchLinkMeta($link))->handle(app(MetaFetchService::class));

    expect($link->fresh()->description)->toBe('My own note');
});

test('does nothing when meta fetch returns nulls', function () {
    $link = Link::factory()->create([
        'url' => 'https://example.com',
        'title' => 'Example',
        'description' => null,
        'bucket_id' => $this->inbox->id,
    ]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => null, 'description' => null, 'favicon_url' => null]);

    (new FetchLinkMeta($link))->handle(app(MetaFetchService::class));

    expect($link->fresh()->description)->toBeNull();
    expect($link->fresh()->title)->toBe('Example');
});
