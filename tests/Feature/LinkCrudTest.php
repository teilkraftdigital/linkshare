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

test('links index lists all links', function () {
    Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);

    $this->get(route('dashboard.links.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Links')
            ->has('links.data', 3)
            ->has('buckets')
            ->has('tags')
        );
});

test('guests are redirected from links index', function () {
    auth()->logout();

    $this->get(route('dashboard.links.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can create a link with all fields', function () {
    $tag = Tag::factory()->create();

    $this->post(route('dashboard.links.store'), [
        'url' => 'https://example.com',
        'title' => 'Example',
        'description' => 'A description',
        'notes' => 'Private note',
        'bucket_id' => $this->inbox->id,
        'tag_ids' => [$tag->id],
    ])->assertRedirect();

    $link = Link::first();
    expect($link->url)->toBe('https://example.com');
    expect($link->title)->toBe('Example');
    expect($link->notes)->toBe('Private note');
    expect($link->tags)->toHaveCount(1);
});

test('authenticated user can create a link without optional fields', function () {
    $this->post(route('dashboard.links.store'), [
        'url' => 'https://example.com',
        'title' => 'Minimal Link',
        'bucket_id' => $this->inbox->id,
    ])->assertRedirect();

    $link = Link::first();
    expect($link->description)->toBeNull();
    expect($link->notes)->toBeNull();
    expect($link->tags)->toHaveCount(0);
});

test('store validates required fields', function () {
    $this->post(route('dashboard.links.store'), [])
        ->assertSessionHasErrors(['url', 'title', 'bucket_id']);
});

test('authenticated user can update a link', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $tag = Tag::factory()->create();
    $other = Bucket::factory()->create();

    $this->patch(route('dashboard.links.update', $link), [
        'url' => 'https://updated.com',
        'title' => 'Updated Title',
        'bucket_id' => $other->id,
        'tag_ids' => [$tag->id],
    ])->assertRedirect();

    expect($link->fresh()->url)->toBe('https://updated.com');
    expect($link->fresh()->bucket_id)->toBe($other->id);
    expect($link->fresh()->tags)->toHaveCount(1);
});

test('authenticated user can delete a link (soft delete)', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->delete(route('dashboard.links.destroy', $link))
        ->assertRedirect();

    expect($link->fresh()->deleted_at)->not->toBeNull();
    $this->assertDatabaseHas('links', ['id' => $link->id]);
});

test('link notes are not accessible to unauthenticated users', function () {
    Link::factory()->withNotes()->create(['bucket_id' => $this->inbox->id]);
    auth()->logout();

    $this->get(route('dashboard.links.index'))
        ->assertRedirect(route('login'));
});
