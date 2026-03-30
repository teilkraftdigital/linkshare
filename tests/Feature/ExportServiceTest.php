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

test('notes is always null', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'notes' => 'secret note']);

    $payload = $this->service->build();

    expect($payload['links'][0]['notes'])->toBeNull();
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
