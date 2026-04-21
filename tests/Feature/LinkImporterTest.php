<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use App\Services\Import\ImportOptions;
use App\Services\Import\LinkImporter;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    User::factory()->create();
    $this->inbox = Bucket::factory()->inbox()->create();
    $this->bucket = Bucket::factory()->create(['name' => 'Work']);
    $this->tag = Tag::factory()->create(['name' => 'php', 'slug' => 'php']);
    $this->importer = app(LinkImporter::class);
    Queue::fake();
});

function linkData(array $overrides = []): array
{
    return array_merge([
        'url' => 'https://example.com',
        'title' => 'Example',
        'description' => null,
        'notes' => null,
        'bucket' => 'Work',
        'tags' => [],
    ], $overrides);
}

test('creates a new link in the correct bucket', function () {
    $bucketMap = ['work' => $this->bucket];

    $result = $this->importer->import([linkData()], $bucketMap, [], ImportOptions::all());

    expect($result['imported'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect(Link::where('url', 'https://example.com')->first()->bucket_id)->toBe($this->bucket->id);
});

test('attaches tags to new link', function () {
    $bucketMap = ['work' => $this->bucket];
    $tagMap = ['php' => $this->tag];

    $this->importer->import([linkData(['tags' => ['php']])], $bucketMap, $tagMap, ImportOptions::all());

    $link = Link::where('url', 'https://example.com')->first();
    expect($link->tags->pluck('name')->toArray())->toContain('php');
});

test('skips link already in database', function () {
    Link::factory()->create(['url' => 'https://example.com']);
    $bucketMap = ['work' => $this->bucket];

    $result = $this->importer->import([linkData()], $bucketMap, [], ImportOptions::all());

    expect($result['imported'])->toBe(0);
    expect($result['skipped'])->toBe(1);
    expect(Link::where('url', 'https://example.com')->count())->toBe(1);
});

test('skips within-file duplicate urls', function () {
    $bucketMap = ['work' => $this->bucket];

    $result = $this->importer->import(
        [linkData(), linkData(['title' => 'Duplicate'])],
        $bucketMap,
        [],
        ImportOptions::all(),
    );

    expect($result['imported'])->toBe(1);
    expect($result['skipped'])->toBe(1);
});

test('skips link with empty url', function () {
    $result = $this->importer->import([linkData(['url' => ''])], [], [], ImportOptions::all());

    expect($result['skipped'])->toBe(1);
});

test('falls back to inbox when bucket not in map and options allow all buckets', function () {
    $result = $this->importer->import(
        [linkData(['bucket' => 'Unknown'])],
        [],
        [],
        ImportOptions::all(),
    );

    expect($result['imported'])->toBe(1);
    expect(Link::first()->bucket_id)->toBe($this->inbox->id);
});

test('skips link when bucket not in map and options are selective', function () {
    $result = $this->importer->import(
        [linkData(['bucket' => 'Work'])],
        [],
        [],
        ImportOptions::selected(['Personal'], []),
    );

    expect($result['skipped'])->toBe(1);
    expect(Link::count())->toBe(0);
});

test('dispatches FetchLinkMeta job for each imported link', function () {
    $bucketMap = ['work' => $this->bucket];

    $this->importer->import([linkData(), linkData(['url' => 'https://other.com'])], $bucketMap, [], ImportOptions::all());

    Queue::assertCount(2);
});
