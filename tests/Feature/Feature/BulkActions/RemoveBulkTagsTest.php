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

test('user can remove tags from multiple links', function () {
    $links = Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);
    $tag = Tag::factory()->create();
    $links->each(fn (Link $link) => $link->tags()->attach($tag));

    $this->post(route('dashboard.links.bulk-remove-tags'), [
        'link_ids' => $links->pluck('id')->all(),
        'tag_ids' => [$tag->id],
    ])->assertRedirect();

    $links->each(fn (Link $link) => expect($link->fresh()->tags->pluck('id'))->not->toContain($tag->id));
});

test('does not remove other tags when removing one', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $keep = Tag::factory()->create();
    $remove = Tag::factory()->create();
    $link->tags()->attach([$keep->id, $remove->id]);

    $this->post(route('dashboard.links.bulk-remove-tags'), [
        'link_ids' => [$link->id],
        'tag_ids' => [$remove->id],
    ])->assertRedirect();

    expect($link->fresh()->tags->pluck('id'))
        ->toContain($keep->id)
        ->not->toContain($remove->id);
});

test('requires at least one tag id', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->post(route('dashboard.links.bulk-remove-tags'), [
        'link_ids' => [$link->id],
        'tag_ids' => [],
    ])->assertSessionHasErrors('tag_ids');
});

test('unauthenticated user cannot bulk remove tags', function () {
    auth()->logout();
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $tag = Tag::factory()->create();

    $this->post(route('dashboard.links.bulk-remove-tags'), [
        'link_ids' => [$link->id],
        'tag_ids' => [$tag->id],
    ])->assertRedirect(route('login'));
});
