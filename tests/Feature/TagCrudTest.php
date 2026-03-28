<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('tags index lists all tags', function () {
    Tag::factory()->count(3)->create();

    $this->get(route('dashboard.tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Tags')
            ->has('tags', 3)
        );
});

test('guests are redirected from tags index', function () {
    auth()->logout();

    $this->get(route('dashboard.tags.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can create a tag', function () {
    $this->post(route('dashboard.tags.store'), [
        'name' => 'Frontend',
        'color' => 'blue',
        'is_public' => false,
    ])->assertRedirect();

    $this->assertDatabaseHas('tags', ['name' => 'Frontend', 'slug' => 'frontend', 'color' => 'blue']);
});

test('store auto-generates slug from name', function () {
    $this->post(route('dashboard.tags.store'), [
        'name' => 'My Cool Tag',
        'color' => 'green',
        'is_public' => false,
    ]);

    expect(Tag::first()->slug)->toBe('my-cool-tag');
});

test('store validates required fields', function () {
    $this->post(route('dashboard.tags.store'), [])
        ->assertSessionHasErrors(['name', 'color']);
});

test('store validates color is from allowed palette', function () {
    $this->post(route('dashboard.tags.store'), [
        'name' => 'Test',
        'color' => 'hotpink',
        'is_public' => false,
    ])->assertSessionHasErrors(['color']);
});

test('authenticated user can update a tag', function () {
    $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old', 'color' => 'gray']);

    $this->patch(route('dashboard.tags.update', $tag), [
        'name' => 'New Name',
        'color' => 'blue',
        'is_public' => true,
    ])->assertRedirect();

    expect($tag->fresh()->name)->toBe('New Name');
    expect($tag->fresh()->slug)->toBe('new-name');
    expect($tag->fresh()->is_public)->toBeTrue();
});

test('update regenerates slug from new name', function () {
    $tag = Tag::factory()->create(['name' => 'Original', 'slug' => 'original']);

    $this->patch(route('dashboard.tags.update', $tag), [
        'name' => 'Renamed Tag',
        'color' => 'gray',
        'is_public' => false,
    ]);

    expect($tag->fresh()->slug)->toBe('renamed-tag');
});

test('update preserves slug when name unchanged', function () {
    $tag = Tag::factory()->create(['name' => 'Frontend', 'slug' => 'frontend']);

    $this->patch(route('dashboard.tags.update', $tag), [
        'name' => 'Frontend',
        'color' => 'blue',
        'is_public' => false,
    ]);

    expect($tag->fresh()->slug)->toBe('frontend');
});

test('tags index includes links_count', function () {
    $inbox = Bucket::factory()->inbox()->create();
    $tag = Tag::factory()->create();
    Link::factory()->count(3)->create(['bucket_id' => $inbox->id])->each(
        fn ($link) => $link->tags()->attach($tag),
    );

    $this->get(route('dashboard.tags.index'))
        ->assertInertia(fn ($page) => $page
            ->where('tags.0.links_count', 3)
        );
});

test('authenticated user can delete a tag', function () {
    $tag = Tag::factory()->create();

    $this->delete(route('dashboard.tags.destroy', $tag))
        ->assertRedirect();

    expect($tag->fresh()->deleted_at)->not->toBeNull();
});
