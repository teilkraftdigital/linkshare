<?php

use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ─── Delete: cascade ────────────────────────────────────────────────────────

test('deleting a root tag with cascade=true soft-deletes parent and all children', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child1 = Tag::factory()->childOf($parent)->create(['name' => 'UI']);
    $child2 = Tag::factory()->childOf($parent)->create(['name' => 'Pinia']);

    $this->delete(route('dashboard.tags.destroy', $parent), ['cascade' => true])
        ->assertRedirect();

    expect($parent->fresh()->deleted_at)->not->toBeNull();
    expect($child1->fresh()->deleted_at)->not->toBeNull();
    expect($child2->fresh()->deleted_at)->not->toBeNull();
});

// ─── Delete: orphan ──────────────────────────────────────────────────────────

test('deleting a root tag with cascade=false orphans children (sets parent_id to null)', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child1 = Tag::factory()->childOf($parent)->create(['name' => 'UI']);
    $child2 = Tag::factory()->childOf($parent)->create(['name' => 'Pinia']);

    $this->delete(route('dashboard.tags.destroy', $parent), ['cascade' => false])
        ->assertRedirect();

    expect($parent->fresh()->deleted_at)->not->toBeNull();
    expect($child1->fresh()->deleted_at)->toBeNull();
    expect($child1->fresh()->parent_id)->toBeNull();
    expect($child2->fresh()->deleted_at)->toBeNull();
    expect($child2->fresh()->parent_id)->toBeNull();
});

test('deleting a tag without cascade parameter defaults to orphan behaviour', function () {
    $parent = Tag::factory()->create(['name' => 'React', 'color' => 'cyan', 'is_public' => false]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'Hooks']);

    $this->delete(route('dashboard.tags.destroy', $parent))
        ->assertRedirect();

    expect($parent->fresh()->deleted_at)->not->toBeNull();
    expect($child->fresh()->deleted_at)->toBeNull();
    expect($child->fresh()->parent_id)->toBeNull();
});

test('deleting a child tag only deletes that child', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'UI']);

    $this->delete(route('dashboard.tags.destroy', $child))
        ->assertRedirect();

    expect($child->fresh()->deleted_at)->not->toBeNull();
    expect($parent->fresh()->deleted_at)->toBeNull();
});

// ─── Restore: simple ────────────────────────────────────────────────────────

test('restoring a trashed root tag (no children) restores it normally', function () {
    $tag = Tag::factory()->create(['name' => 'Vue']);
    $tag->delete();

    $this->post(route('dashboard.tags.restore', $tag))
        ->assertRedirect();

    expect($tag->fresh()->deleted_at)->toBeNull();
});

// ─── Restore: with selected children ────────────────────────────────────────

test('restoring with child_ids restores parent and selected children', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child1 = Tag::factory()->childOf($parent)->create(['name' => 'UI']);
    $child2 = Tag::factory()->childOf($parent)->create(['name' => 'Pinia']);

    $parent->delete();
    $child1->delete();
    $child2->delete();

    $this->post(route('dashboard.tags.restore', $parent), ['child_ids' => [$child1->id]])
        ->assertRedirect();

    expect($parent->fresh()->deleted_at)->toBeNull();
    expect($child1->fresh()->deleted_at)->toBeNull();
    // child2 was not included in child_ids — stays trashed
    expect($child2->fresh()->deleted_at)->not->toBeNull();
});

test('restoring with all children restores everything', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child1 = Tag::factory()->childOf($parent)->create(['name' => 'UI']);
    $child2 = Tag::factory()->childOf($parent)->create(['name' => 'Pinia']);

    $parent->delete();
    $child1->delete();
    $child2->delete();

    $this->post(route('dashboard.tags.restore', $parent), ['child_ids' => [$child1->id, $child2->id]])
        ->assertRedirect();

    expect($parent->fresh()->deleted_at)->toBeNull();
    expect($child1->fresh()->deleted_at)->toBeNull();
    expect($child2->fresh()->deleted_at)->toBeNull();
});

test('child_ids from a different parent are not restored', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $otherParent = Tag::factory()->create(['name' => 'React', 'color' => 'cyan', 'is_public' => false]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'UI']);
    $otherChild = Tag::factory()->childOf($otherParent)->create(['name' => 'Hooks']);

    $parent->delete();
    $child->delete();
    $otherChild->delete();

    // Try to sneak in otherChild's ID
    $this->post(route('dashboard.tags.restore', $parent), ['child_ids' => [$child->id, $otherChild->id]])
        ->assertRedirect();

    expect($child->fresh()->deleted_at)->toBeNull();
    // otherChild not a child of $parent — must stay trashed
    expect($otherChild->fresh()->deleted_at)->not->toBeNull();
});

// ─── Restore: orphan ────────────────────────────────────────────────────────

test('restoring with orphan=true sets parent_id to null', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'UI']);

    $parent->delete();
    $child->delete();

    $this->post(route('dashboard.tags.restore', $child), ['orphan' => true])
        ->assertRedirect();

    expect($child->fresh()->deleted_at)->toBeNull();
    expect($child->fresh()->parent_id)->toBeNull();
});

// ─── Trash index: parent_trashed flag ───────────────────────────────────────

test('trash index marks child tags whose parent is also trashed', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'UI']);

    $parent->delete();
    $child->delete();

    $this->get(route('dashboard.tags.index', ['trashed' => '1']))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Tags')
            ->where('tags', fn ($tags) => collect($tags)->firstWhere('name', 'UI')['parent_trashed'] === true)
        );
});

test('trash index marks child tags whose parent is active as parent_trashed=false', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'color' => 'blue', 'is_public' => false]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'UI']);

    // Only delete the child, not the parent
    $child->delete();

    $this->get(route('dashboard.tags.index', ['trashed' => '1']))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Tags')
            ->where('tags', fn ($tags) => collect($tags)->firstWhere('name', 'UI')['parent_trashed'] === false)
        );
});
