<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Bucket::factory()->inbox()->create();
});

test('guests are redirected to the login page', function () {
    auth()->logout();
    $this->get(route('dashboard.index'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->get(route('dashboard.index'))->assertOk();
});

test('dashboard returns link count stat', function () {
    $bucket = Bucket::factory()->create();
    Link::factory()->count(3)->create(['bucket_id' => $bucket->id]);

    $this->get(route('dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.links.count', 3)
        );
});

test('dashboard returns 7-day link delta', function () {
    $bucket = Bucket::factory()->create();
    Link::factory()->count(2)->create(['bucket_id' => $bucket->id, 'created_at' => now()->subDays(3)]);
    Link::factory()->count(1)->create(['bucket_id' => $bucket->id, 'created_at' => now()->subDays(10)]);

    $this->get(route('dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.links.delta', 2)
        );
});

test('dashboard returns tag count stat', function () {
    Tag::factory()->count(4)->create();

    $this->get(route('dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.tags.count', 4)
        );
});

test('dashboard returns public tag count stat', function () {
    Tag::factory()->count(3)->public()->create();
    Tag::factory()->count(2)->create();

    $this->get(route('dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.public_tags.count', 3)
        );
});

test('dashboard returns last 10 recent links sorted newest first', function () {
    $bucket = Bucket::factory()->create();
    Link::factory()->count(12)->create(['bucket_id' => $bucket->id]);

    $this->get(route('dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->has('recent_links', 10)
        );
});

test('dashboard recent links include favicon url, bucket, and tags', function () {
    $bucket = Bucket::factory()->create();
    $tag = Tag::factory()->create();
    $link = Link::factory()->create(['bucket_id' => $bucket->id]);
    $link->tags()->attach($tag);

    $this->get(route('dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->has('recent_links.0', fn ($l) => $l
                ->hasAll(['id', 'url', 'title', 'favicon_url', 'bucket', 'tags'])
                ->where('bucket.id', $bucket->id)
                ->etc()
            )
        );
});

test('dashboard tags include link count', function () {
    $bucket = Bucket::factory()->create();
    $tag = Tag::factory()->create();
    $link = Link::factory()->create(['bucket_id' => $bucket->id]);
    $link->tags()->attach($tag);

    $this->get(route('dashboard.index'))
        ->assertInertia(fn ($page) => $page
            ->has('tags', 1, fn ($t) => $t
                ->where('id', $tag->id)
                ->where('links_count', 1)
                ->etc()
            )
        );
});

test('dashboard data is cached after first request', function () {
    Cache::forget('dashboard');

    $this->get(route('dashboard.index'));

    expect(Cache::has('dashboard'))->toBeTrue();
});

test('cache is invalidated when a link is created', function () {
    $this->get(route('dashboard.index'));
    expect(Cache::has('dashboard'))->toBeTrue();

    $bucket = Bucket::factory()->create();
    Link::factory()->create(['bucket_id' => $bucket->id]);

    expect(Cache::has('dashboard'))->toBeFalse();
});

test('cache is invalidated when a tag is created', function () {
    $this->get(route('dashboard.index'));

    Tag::factory()->create();

    expect(Cache::has('dashboard'))->toBeFalse();
});

test('cache is invalidated when a bucket is updated', function () {
    $bucket = Bucket::factory()->create();
    $this->get(route('dashboard.index'));

    $bucket->update(['name' => 'Renamed']);

    expect(Cache::has('dashboard'))->toBeFalse();
});

test('tag updated_at is touched when a link is associated via store', function () {
    $bucket = Bucket::factory()->create();
    $tag = Tag::factory()->create();
    $before = $tag->updated_at;

    $this->travel(2)->seconds();

    $this->post(route('dashboard.links.store'), [
        'url' => 'https://example.com',
        'bucket_id' => $bucket->id,
        'tag_ids' => [$tag->id],
    ]);

    $tag->refresh();
    expect($tag->updated_at->isAfter($before))->toBeTrue();
});

test('tag updated_at is touched when a link association is removed via update', function () {
    $bucket = Bucket::factory()->create();
    $tag = Tag::factory()->create();
    $link = Link::factory()->create(['bucket_id' => $bucket->id]);
    $link->tags()->sync([$tag->id]);
    $tag->refresh();

    $before = $tag->updated_at;
    $this->travel(2)->seconds();

    $this->patch(route('dashboard.links.update', $link), [
        'url' => $link->url,
        'title' => $link->title,
        'bucket_id' => $bucket->id,
        'tag_ids' => [],
    ]);

    $tag->refresh();
    expect($tag->updated_at->isAfter($before))->toBeTrue();
});
