<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Services\LinkQueryBuilder;

beforeEach(function () {
    $this->inbox = Bucket::factory()->inbox()->create();
    $this->builder = app(LinkQueryBuilder::class);
});

test('returns all links when no filters applied', function () {
    Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);

    $result = $this->builder->paginate([]);

    expect($result->total())->toBe(3);
});

test('filters by bucket', function () {
    $other = Bucket::factory()->create();
    Link::factory()->count(2)->create(['bucket_id' => $this->inbox->id]);
    Link::factory()->count(3)->create(['bucket_id' => $other->id]);

    $result = $this->builder->paginate(['bucket_id' => $other->id]);

    expect($result->total())->toBe(3);
    expect($result->items())->each(fn ($link) => $link->bucket_id->toBe($other->id));
});

test('filters by tag', function () {
    $tag = Tag::factory()->create();
    $tagged = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $tagged->tags()->attach($tag);
    Link::factory()->count(2)->create(['bucket_id' => $this->inbox->id]);

    $result = $this->builder->paginate(['tag_id' => $tag->id]);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->id)->toBe($tagged->id);
});

test('filters by search in title', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'title' => 'Laravel Tutorial']);
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'title' => 'Vue Basics']);

    $result = $this->builder->paginate(['search' => 'Laravel']);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->title)->toBe('Laravel Tutorial');
});

test('filters by search in url', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'url' => 'https://laravel.com', 'title' => 'A']);
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'url' => 'https://vuejs.org', 'title' => 'B']);

    $result = $this->builder->paginate(['search' => 'laravel.com']);

    expect($result->total())->toBe(1);
});

test('filters by search in description', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'description' => 'A great PHP framework', 'title' => 'A']);
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'description' => 'JavaScript tooling', 'title' => 'B']);

    $result = $this->builder->paginate(['search' => 'PHP framework']);

    expect($result->total())->toBe(1);
});

test('combines bucket and tag filters', function () {
    $other = Bucket::factory()->create();
    $tag = Tag::factory()->create();

    $match = Link::factory()->create(['bucket_id' => $other->id]);
    $match->tags()->attach($tag);

    Link::factory()->create(['bucket_id' => $other->id]); // same bucket, no tag
    $wrongBucket = Link::factory()->create(['bucket_id' => $this->inbox->id]); // tag but wrong bucket
    $wrongBucket->tags()->attach($tag);

    $result = $this->builder->paginate(['bucket_id' => $other->id, 'tag_id' => $tag->id]);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->id)->toBe($match->id);
});

test('returns empty result when no links match', function () {
    Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);

    $result = $this->builder->paginate(['search' => 'xyznonexistent']);

    expect($result->total())->toBe(0);
    expect($result->items())->toBeEmpty();
});

test('paginates with 25 per page', function () {
    Link::factory()->count(30)->create(['bucket_id' => $this->inbox->id]);

    $result = $this->builder->paginate([]);

    expect($result->perPage())->toBe(LinkQueryBuilder::PER_PAGE);
    expect($result->count())->toBe(25);
    expect($result->total())->toBe(30);
});
