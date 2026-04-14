<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('user can restore multiple trashed links', function () {
    $links = Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);
    $links->each->delete();

    $this->post(route('dashboard.links.bulk-restore'), [
        'link_ids' => $links->pluck('id')->all(),
    ])->assertRedirect();

    $links->each(fn (Link $link) => expect($link->fresh()->trashed())->toBeFalse());
});

test('requires at least one link id', function () {
    $this->post(route('dashboard.links.bulk-restore'), [
        'link_ids' => [],
    ])->assertSessionHasErrors('link_ids');
});

test('rejects ids of non-trashed links', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->post(route('dashboard.links.bulk-restore'), [
        'link_ids' => [$link->id],
    ])->assertSessionHasErrors('link_ids.0');
});

test('unauthenticated user cannot bulk restore', function () {
    auth()->logout();

    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $this->post(route('dashboard.links.bulk-restore'), [
        'link_ids' => [$link->id],
    ])->assertRedirect(route('login'));
});
