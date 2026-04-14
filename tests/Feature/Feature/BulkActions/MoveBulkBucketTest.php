<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('user can move multiple links to another bucket', function () {
    $target = Bucket::factory()->create();
    $links = Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);

    $this->patch(route('dashboard.links.bulk-move-bucket'), [
        'link_ids' => $links->pluck('id')->all(),
        'bucket_id' => $target->id,
    ])->assertRedirect();

    $links->each(fn (Link $link) => expect($link->fresh()->bucket_id)->toBe($target->id));
});

test('requires bucket_id', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->patch(route('dashboard.links.bulk-move-bucket'), [
        'link_ids' => [$link->id],
    ])->assertSessionHasErrors('bucket_id');
});

test('rejects non-existent bucket_id', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->patch(route('dashboard.links.bulk-move-bucket'), [
        'link_ids' => [$link->id],
        'bucket_id' => 99999,
    ])->assertSessionHasErrors('bucket_id');
});

test('requires at least one link id', function () {
    $target = Bucket::factory()->create();

    $this->patch(route('dashboard.links.bulk-move-bucket'), [
        'link_ids' => [],
        'bucket_id' => $target->id,
    ])->assertSessionHasErrors('link_ids');
});

test('unauthenticated user cannot bulk move', function () {
    auth()->logout();
    $target = Bucket::factory()->create();
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->patch(route('dashboard.links.bulk-move-bucket'), [
        'link_ids' => [$link->id],
        'bucket_id' => $target->id,
    ])->assertRedirect(route('login'));
});
