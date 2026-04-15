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

test('user can add tags to multiple links', function () {
    $links = Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);
    $tag = Tag::factory()->create();

    $this->post(route('dashboard.links.bulk-add-tags'), [
        'link_ids' => $links->pluck('id')->all(),
        'tag_ids' => [$tag->id],
    ])->assertRedirect();

    $links->each(fn (Link $link) => expect($link->fresh()->tags->pluck('id'))->toContain($tag->id));
});

test('does not remove existing tags when adding new ones', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $existing = Tag::factory()->create();
    $new = Tag::factory()->create();
    $link->tags()->attach($existing);

    $this->post(route('dashboard.links.bulk-add-tags'), [
        'link_ids' => [$link->id],
        'tag_ids' => [$new->id],
    ])->assertRedirect();

    expect($link->fresh()->tags->pluck('id'))
        ->toContain($existing->id)
        ->toContain($new->id);
});

test('requires at least one tag id', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->post(route('dashboard.links.bulk-add-tags'), [
        'link_ids' => [$link->id],
        'tag_ids' => [],
    ])->assertSessionHasErrors('tag_ids');
});

test('unauthenticated user cannot bulk add tags', function () {
    auth()->logout();
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $tag = Tag::factory()->create();

    $this->post(route('dashboard.links.bulk-add-tags'), [
        'link_ids' => [$link->id],
        'tag_ids' => [$tag->id],
    ])->assertRedirect(route('login'));
});
