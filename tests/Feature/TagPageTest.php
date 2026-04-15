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

test('child tag page returns 404', function () {
    $parent = Tag::factory()->public()->create();
    $child = Tag::factory()->childOf($parent)->create();

    $this->get(route('tags.show', $child))
        ->assertNotFound();
});

test('public tag page includes child tag links grouped by child', function () {
    $parent = Tag::factory()->public()->create();
    $child = Tag::factory()->childOf($parent)->create(['name' => 'Pinia', 'slug' => 'pinia']);
    $inbox = Bucket::factory()->inbox()->create();

    $parentLink = Link::factory()->create(['title' => 'Vue Docs', 'bucket_id' => $inbox->id]);
    $parentLink->tags()->attach($parent);

    $childLink = Link::factory()->create(['title' => 'Pinia Docs', 'bucket_id' => $inbox->id]);
    $childLink->tags()->attach($child);

    $this->get(route('tags.show', $parent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links', 1)
            ->has('children', 1)
            ->where('children.0.name', 'Pinia')
            ->where('children.0.slug', 'pinia')
            ->has('children.0.links', 1)
            ->where('children.0.links.0.title', 'Pinia Docs')
        );
});

test('public tag page omits allgemein section when parent has no direct links', function () {
    $parent = Tag::factory()->public()->create();
    $child = Tag::factory()->childOf($parent)->create();
    $inbox = Bucket::factory()->inbox()->create();

    $childLink = Link::factory()->create(['bucket_id' => $inbox->id]);
    $childLink->tags()->attach($child);

    $this->get(route('tags.show', $parent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links', 0)
            ->has('children', 1)
        );
});

test('public tag page passes child description', function () {
    $parent = Tag::factory()->public()->create();
    $child = Tag::factory()->childOf($parent)->create(['description' => 'State management for Vue']);
    $inbox = Bucket::factory()->inbox()->create();

    $link = Link::factory()->create(['bucket_id' => $inbox->id]);
    $link->tags()->attach($child);

    $this->get(route('tags.show', $parent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('children.0.description', 'State management for Vue')
        );
});

test('public tag page omits child tags with no links', function () {
    $parent = Tag::factory()->public()->create();
    Tag::factory()->childOf($parent)->create(); // child without links

    $this->get(route('tags.show', $parent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('children', 0)
        );
});
