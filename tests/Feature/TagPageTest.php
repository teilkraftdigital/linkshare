<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;

test('public tag page renders with its links', function () {
    $tag = Tag::factory()->public()->create();
    $inbox = Bucket::factory()->inbox()->create();
    $links = Link::factory()->count(2)->create(['bucket_id' => $inbox->id]);
    $links->each(fn ($link) => $link->tags()->attach($tag));

    $this->get(route('tags.show', $tag))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tags/Show')
            ->where('tag.name', $tag->name)
            ->where('tag.slug', $tag->slug)
            ->has('links', 2)
        );
});

test('public tag page does not expose notes', function () {
    $tag = Tag::factory()->public()->create();
    $inbox = Bucket::factory()->inbox()->create();
    $link = Link::factory()->withNotes()->create(['bucket_id' => $inbox->id]);
    $link->tags()->attach($tag);

    $this->get(route('tags.show', $tag))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links', 1)
            ->missing('links.0.notes')
        );
});

test('private tag returns 404', function () {
    $tag = Tag::factory()->create(['is_public' => false]);

    $this->get(route('tags.show', $tag))
        ->assertNotFound();
});

test('public tag page is accessible without login', function () {
    $tag = Tag::factory()->public()->create();

    $this->get(route('tags.show', $tag))
        ->assertOk();
});
