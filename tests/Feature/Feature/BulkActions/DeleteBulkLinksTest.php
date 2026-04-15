<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('user can soft delete multiple links', function () {
    $links = Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);

    $this->delete(route('dashboard.links.bulk-delete'), [
        'link_ids' => $links->pluck('id')->all(),
    ])->assertRedirect();

    $links->each(fn (Link $link) => expect($link->fresh()->trashed())->toBeTrue());
});

test('soft deleted links remain in database', function () {
    $links = Link::factory()->count(2)->create(['bucket_id' => $this->inbox->id]);

    $this->delete(route('dashboard.links.bulk-delete'), [
        'link_ids' => $links->pluck('id')->all(),
    ])->assertRedirect();

    foreach ($links as $link) {
        $this->assertSoftDeleted('links', ['id' => $link->id]);
    }
});

test('requires at least one link id', function () {
    $this->delete(route('dashboard.links.bulk-delete'), [
        'link_ids' => [],
    ])->assertSessionHasErrors('link_ids');
});

test('rejects non-existent link ids', function () {
    $this->delete(route('dashboard.links.bulk-delete'), [
        'link_ids' => [99999],
    ])->assertSessionHasErrors('link_ids.0');
});

test('unauthenticated user cannot bulk delete', function () {
    auth()->logout();

    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->delete(route('dashboard.links.bulk-delete'), [
        'link_ids' => [$link->id],
    ])->assertRedirect(route('login'));
});
