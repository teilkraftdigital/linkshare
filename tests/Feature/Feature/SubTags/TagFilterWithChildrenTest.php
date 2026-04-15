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

test('filtering by parent tag includes links with child tags', function () {
    $vue = Tag::factory()->create(['slug' => 'vue']);
    $pinia = Tag::factory()->childOf($vue)->create(['slug' => 'pinia']);

    $directLink = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $directLink->tags()->attach($vue);

    $childLink = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $childLink->tags()->attach($pinia);

    $this->get(route('dashboard.links.index', ['tag_id' => $vue->id]))
        ->assertInertia(fn ($page) => $page
            ->where('links.total', 2)
        );
});

test('filtering by child tag only returns direct matches', function () {
    $vue = Tag::factory()->create(['slug' => 'vue']);
    $pinia = Tag::factory()->childOf($vue)->create(['slug' => 'pinia']);

    $directLink = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $directLink->tags()->attach($vue);

    $childLink = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $childLink->tags()->attach($pinia);

    $this->get(route('dashboard.links.index', ['tag_id' => $pinia->id]))
        ->assertInertia(fn ($page) => $page
            ->where('links.total', 1)
        );
});

test('filtering by parent tag does not include links from other parents children', function () {
    $vue = Tag::factory()->create(['slug' => 'vue']);
    $react = Tag::factory()->create(['slug' => 'react']);
    $reactUi = Tag::factory()->childOf($react)->create(['slug' => 'ui']);

    $reactLink = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $reactLink->tags()->attach($reactUi);

    $this->get(route('dashboard.links.index', ['tag_id' => $vue->id]))
        ->assertInertia(fn ($page) => $page
            ->where('links.total', 0)
        );
});
