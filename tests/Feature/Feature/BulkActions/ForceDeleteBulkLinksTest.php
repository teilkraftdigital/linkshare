<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('user can force delete multiple trashed links', function () {
    $links = Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);
    $links->each->delete();

    $this->delete(route('dashboard.links.bulk-force-delete'), [
        'link_ids' => $links->pluck('id')->all(),
    ])->assertRedirect();

    foreach ($links as $link) {
        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }
});

test('cannot force delete non-trashed links', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->delete(route('dashboard.links.bulk-force-delete'), [
        'link_ids' => [$link->id],
    ])->assertSessionHasErrors('link_ids.0');

    $this->assertDatabaseHas('links', ['id' => $link->id]);
});

test('requires at least one link id', function () {
    $this->delete(route('dashboard.links.bulk-force-delete'), [
        'link_ids' => [],
    ])->assertSessionHasErrors('link_ids');
});

test('unauthenticated user cannot bulk force delete', function () {
    auth()->logout();

    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $this->delete(route('dashboard.links.bulk-force-delete'), [
        'link_ids' => [$link->id],
    ])->assertRedirect(route('login'));
});
