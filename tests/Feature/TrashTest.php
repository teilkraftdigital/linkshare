<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->inbox = Bucket::factory()->inbox()->create();
});

// — Links —

test('trashed links appear in trash view', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $this->get(route('dashboard.links.index', ['trashed' => '1']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links.data', 1)
            ->where('showTrashed', true)
        );
});

test('trashed links do not appear in normal view', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $this->get(route('dashboard.links.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links.data', 0)
        );
});

test('user can restore a trashed link', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $this->post(route('dashboard.links.restore', $link))
        ->assertRedirect();

    expect($link->fresh()->deleted_at)->toBeNull();
});

test('user can force delete a trashed link', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $this->delete(route('dashboard.links.force-delete', $link))
        ->assertRedirect();

    $this->assertDatabaseMissing('links', ['id' => $link->id]);
});

// — Buckets —

test('trashed buckets appear in trash view', function () {
    $bucket = Bucket::factory()->create();
    $bucket->delete();

    $this->get(route('dashboard.buckets.index', ['trashed' => '1']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('buckets', 1)
            ->where('showTrashed', true)
        );
});

test('trashed buckets do not appear in normal view', function () {
    $bucket = Bucket::factory()->create();
    $bucket->delete();

    $this->get(route('dashboard.buckets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('buckets', 1) // only inbox
        );
});

test('user can restore a trashed bucket', function () {
    $bucket = Bucket::factory()->create();
    $bucket->delete();

    $this->post(route('dashboard.buckets.restore', $bucket))
        ->assertRedirect();

    expect($bucket->fresh()->deleted_at)->toBeNull();
});

test('user can force delete a trashed bucket', function () {
    $bucket = Bucket::factory()->create();
    $bucket->delete();

    $this->delete(route('dashboard.buckets.force-delete', $bucket))
        ->assertRedirect();

    $this->assertDatabaseMissing('buckets', ['id' => $bucket->id]);
});

test('inbox bucket cannot be force deleted', function () {
    $this->inbox->delete();

    $this->delete(route('dashboard.buckets.force-delete', $this->inbox))
        ->assertForbidden();
});

// — Tags —

test('trashed tags appear in trash view', function () {
    $tag = Tag::factory()->create();
    $tag->delete();

    $this->get(route('dashboard.tags.index', ['trashed' => '1']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tags', 1)
            ->where('showTrashed', true)
        );
});

test('trashed tags do not appear in normal view', function () {
    $tag = Tag::factory()->create();
    $tag->delete();

    $this->get(route('dashboard.tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tags', 0)
        );
});

test('user can restore a trashed tag', function () {
    $tag = Tag::factory()->create();
    $tag->delete();

    $this->post(route('dashboard.tags.restore', $tag))
        ->assertRedirect();

    expect($tag->fresh()->deleted_at)->toBeNull();
});

test('user can force delete a trashed tag', function () {
    $tag = Tag::factory()->create();
    $tag->delete();

    $this->delete(route('dashboard.tags.force-delete', $tag))
        ->assertRedirect();

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});

test('force deleting a tag detaches it from all links', function () {
    $tag = Tag::factory()->create();
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->tags()->attach($tag);
    $tag->delete();

    $this->delete(route('dashboard.tags.force-delete', $tag))
        ->assertRedirect();

    expect($link->fresh()->tags)->toHaveCount(0);
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});
