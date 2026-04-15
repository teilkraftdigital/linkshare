<?php

use App\Models\Tag;
use App\Services\SlugGenerator;

test('root tags have globally unique slugs', function () {
    Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue']);

    $slug = app(SlugGenerator::class)->generate('Vue');

    expect($slug)->toBe('vue-2');
});

test('child tags share slugs across different parents', function () {
    $vue = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue']);
    $react = Tag::factory()->create(['name' => 'React', 'slug' => 'react']);

    Tag::factory()->childOf($vue)->create(['name' => 'UI', 'slug' => 'ui']);

    // Generating "ui" under react should not conflict with vue/ui
    $slug = app(SlugGenerator::class)->generate('UI', null, $react->id);

    expect($slug)->toBe('ui');
});

test('child tags are unique within their parent', function () {
    $vue = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue']);
    Tag::factory()->childOf($vue)->create(['name' => 'UI', 'slug' => 'ui']);

    $slug = app(SlugGenerator::class)->generate('UI', null, $vue->id);

    expect($slug)->toBe('ui-2');
});

test('slug counter falls back gracefully for children', function () {
    $vue = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue']);
    Tag::factory()->childOf($vue)->create(['name' => 'UI', 'slug' => 'ui']);
    Tag::factory()->childOf($vue)->create(['name' => 'UI', 'slug' => 'ui-2']);

    $slug = app(SlugGenerator::class)->generate('UI', null, $vue->id);

    expect($slug)->toBe('ui-3');
});

test('ignores soft-deleted tags when checking slug uniqueness within parent scope', function () {
    $vue = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue']);
    $child = Tag::factory()->childOf($vue)->create(['name' => 'UI', 'slug' => 'ui']);
    $child->delete();

    // Soft-deleted child still counts — no reuse until force-deleted
    $slug = app(SlugGenerator::class)->generate('UI', null, $vue->id);

    expect($slug)->toBe('ui-2');
});

test('tag model has parent and children relationships', function () {
    $vue = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue']);
    $pinia = Tag::factory()->childOf($vue)->create(['name' => 'Pinia', 'slug' => 'pinia']);

    expect($pinia->parent->id)->toBe($vue->id);
    expect($vue->children->first()->id)->toBe($pinia->id);
});

test('childOf factory state inherits color and is_public from parent', function () {
    $vue = Tag::factory()->create(['color' => 'green', 'is_public' => true]);
    $child = Tag::factory()->childOf($vue)->create();

    expect($child->color)->toBe('green');
    expect($child->is_public)->toBeTrue();
});
