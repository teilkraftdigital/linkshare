<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('buckets index lists all buckets', function () {
    Bucket::factory()->inbox()->create();
    Bucket::factory()->count(2)->create();

    $this->get(route('dashboard.buckets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Buckets')
            ->has('buckets', 3)
        );
});

test('guests are redirected from buckets index', function () {
    auth()->logout();

    $this->get(route('dashboard.buckets.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can create a bucket', function () {
    $this->post(route('dashboard.buckets.store'), [
        'name' => 'Work',
        'color' => 'blue',
    ])->assertRedirect();

    $this->assertDatabaseHas('buckets', ['name' => 'Work', 'color' => 'blue']);
});

test('store validates required fields', function () {
    $this->post(route('dashboard.buckets.store'), [])
        ->assertSessionHasErrors(['name', 'color']);
});

test('store validates color is from allowed palette', function () {
    $this->post(route('dashboard.buckets.store'), [
        'name' => 'Work',
        'color' => 'hotpink',
    ])->assertSessionHasErrors(['color']);
});

test('authenticated user can update a bucket', function () {
    $bucket = Bucket::factory()->create(['name' => 'Old', 'color' => 'gray']);

    $this->patch(route('dashboard.buckets.update', $bucket), [
        'name' => 'New',
        'color' => 'blue',
    ])->assertRedirect();

    expect($bucket->fresh()->name)->toBe('New');
    expect($bucket->fresh()->color)->toBe('blue');
});

test('authenticated user can delete a non-inbox bucket', function () {
    $bucket = Bucket::factory()->create();

    $this->delete(route('dashboard.buckets.destroy', $bucket))
        ->assertRedirect();

    expect($bucket->fresh()->deleted_at)->not->toBeNull();
});

test('buckets index includes links_count', function () {
    $bucket = Bucket::factory()->inbox()->create();
    Link::factory()->count(2)->create(['bucket_id' => $bucket->id]);

    $this->get(route('dashboard.buckets.index'))
        ->assertInertia(fn ($page) => $page
            ->where('buckets.0.links_count', 2)
        );
});

test('inbox bucket cannot be deleted', function () {
    $inbox = Bucket::factory()->inbox()->create();

    $this->delete(route('dashboard.buckets.destroy', $inbox))
        ->assertForbidden();

    $this->assertModelExists($inbox);
});
