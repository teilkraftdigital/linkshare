<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Services\ExportService;

beforeEach(function () {
    $this->service = app(ExportService::class);
    $this->inbox = Bucket::factory()->inbox()->create(['name' => 'Inbox', 'color' => 'gray']);
});

test('empty app exports valid structure', function () {
    $payload = $this->service->build();

    expect($payload)
        ->toHaveKey('version', 1)
        ->toHaveKey('includes_notes', false)
        ->toHaveKey('exported_at')
        ->toHaveKey('buckets')
        ->toHaveKey('tags')
        ->toHaveKey('links');

    expect($payload['buckets'])->toHaveCount(1); // inbox
    expect($payload['tags'])->toBeEmpty();
    expect($payload['links'])->toBeEmpty();
});

test('buckets include all required fields', function () {
    $payload = $this->service->build();

    expect($payload['buckets'][0])
        ->toHaveKeys(['name', 'color', 'is_inbox'])
        ->name->toBe('Inbox')
        ->is_inbox->toBeTrue();
});

test('tags include all required fields', function () {
    Tag::factory()->create(['name' => 'design', 'slug' => 'design', 'color' => 'blue', 'description' => 'Design links', 'is_public' => true]);

    $payload = $this->service->build();

    expect($payload['tags'])->toHaveCount(1);
    expect($payload['tags'][0])
        ->toHaveKeys(['name', 'slug', 'color', 'description', 'is_public'])
        ->name->toBe('design')
        ->slug->toBe('design')
        ->is_public->toBeTrue()
        ->description->toBe('Design links');
});

test('links reference bucket by name not id', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'url' => 'https://example.com']);

    $payload = $this->service->build();

    expect($payload['links'][0])
        ->toHaveKey('bucket', 'Inbox')
        ->toHaveKey('notes', null);
});

test('links reference tags by name', function () {
    $tag = Tag::factory()->create(['name' => 'php']);
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->tags()->attach($tag);

    $payload = $this->service->build();

    expect($payload['links'][0]['tags'])->toBe(['php']);
});

test('notes is always null when includes_notes is false', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'notes' => 'secret note']);

    $payload = $this->service->build(includesNotes: false);

    expect($payload['links'][0]['notes'])->toBeNull();
    expect($payload['includes_notes'])->toBeFalse();
});

test('notes are included when includes_notes is true', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'notes' => 'private note']);

    $payload = $this->service->build(includesNotes: true);

    expect($payload['links'][0]['notes'])->toBe('private note');
    expect($payload['includes_notes'])->toBeTrue();
});

test('soft deleted entries are not exported', function () {
    $bucket = Bucket::factory()->create();
    $bucket->delete();

    $tag = Tag::factory()->create();
    $tag->delete();

    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $payload = $this->service->build();

    expect($payload['buckets'])->toHaveCount(1); // only inbox
    expect($payload['tags'])->toBeEmpty();
    expect($payload['links'])->toBeEmpty();
});

test('links with no tags export empty tags array', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $payload = $this->service->build();

    expect($payload['links'][0]['tags'])->toBe([]);
});

// — Selective bucket export —

test('only selected buckets are exported', function () {
    $work = Bucket::factory()->create(['name' => 'Work']);
    $personal = Bucket::factory()->create(['name' => 'Personal']);

    Link::factory()->create(['bucket_id' => $work->id]);
    Link::factory()->create(['bucket_id' => $personal->id]);
    Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $payload = $this->service->build(bucketIds: [$work->id]);

    $bucketNames = array_column($payload['buckets'], 'name');
    expect($bucketNames)->toBe(['Work']);
    expect($payload['links'])->toHaveCount(1);
    expect($payload['links'][0]['bucket'])->toBe('Work');
});

test('links from excluded buckets are not exported', function () {
    $work = Bucket::factory()->create(['name' => 'Work']);
    Link::factory()->create(['bucket_id' => $work->id, 'url' => 'https://work.com']);
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'url' => 'https://inbox.com']);

    $payload = $this->service->build(bucketIds: [$this->inbox->id]);

    expect($payload['links'])->toHaveCount(1);
    expect($payload['links'][0]['url'])->toBe('https://inbox.com');
});

// — Selective tag export —

test('only selected tags appear as entities', function () {
    $php = Tag::factory()->create(['name' => 'php']);
    $js = Tag::factory()->create(['name' => 'js']);

    $payload = $this->service->build(tagIds: [$php->id]);

    $tagNames = array_column($payload['tags'], 'name');
    expect($tagNames)->toBe(['php']);
    expect($tagNames)->not->toContain('js');
});

test('excluded tags are stripped from link associations', function () {
    $php = Tag::factory()->create(['name' => 'php']);
    $js = Tag::factory()->create(['name' => 'js']);
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->tags()->attach([$php->id, $js->id]);

    $payload = $this->service->build(tagIds: [$php->id]);

    expect($payload['links'][0]['tags'])->toBe(['php']);
    expect($payload['links'][0]['tags'])->not->toContain('js');
});

test('link with only excluded tags exports empty tags array', function () {
    $js = Tag::factory()->create(['name' => 'js']);
    $php = Tag::factory()->create(['name' => 'php']);
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->tags()->attach([$js->id]);

    $payload = $this->service->build(tagIds: [$php->id]);

    expect($payload['links'][0]['tags'])->toBe([]);
});

// — Combined selections —

test('combined bucket and tag filter works together', function () {
    $work = Bucket::factory()->create(['name' => 'Work']);
    $design = Tag::factory()->create(['name' => 'design']);
    $dev = Tag::factory()->create(['name' => 'dev']);

    $link = Link::factory()->create(['bucket_id' => $work->id]);
    $link->tags()->attach([$design->id, $dev->id]);

    // Export only Work bucket and only design tag
    $payload = $this->service->build(bucketIds: [$work->id], tagIds: [$design->id]);

    expect($payload['buckets'])->toHaveCount(1);
    expect($payload['buckets'][0]['name'])->toBe('Work');
    expect($payload['tags'])->toHaveCount(1);
    expect($payload['tags'][0]['name'])->toBe('design');
    expect($payload['links'])->toHaveCount(1);
    expect($payload['links'][0]['tags'])->toBe(['design']);
});
