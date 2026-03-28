<?php

use App\Models\Tag;
use App\Services\SlugGenerator;

test('generates slug from name', function () {
    $slug = app(SlugGenerator::class)->generate('My Awesome Tag');

    expect($slug)->toBe('my-awesome-tag');
});

test('slugifies umlauts and special characters', function () {
    $slug = app(SlugGenerator::class)->generate('Über Größe & Café');

    expect($slug)->toBe('uber-grosse-cafe');
});

test('appends counter on slug collision', function () {
    Tag::factory()->create(['slug' => 'workshop-a']);

    $slug = app(SlugGenerator::class)->generate('Workshop A');

    expect($slug)->toBe('workshop-a-2');
});

test('increments counter when multiple collisions exist', function () {
    Tag::factory()->create(['slug' => 'design']);
    Tag::factory()->create(['slug' => 'design-2']);

    $slug = app(SlugGenerator::class)->generate('Design');

    expect($slug)->toBe('design-3');
});

test('ignores own entry when updating', function () {
    $tag = Tag::factory()->create(['name' => 'Frontend', 'slug' => 'frontend']);

    $slug = app(SlugGenerator::class)->generate('Frontend', $tag->id);

    expect($slug)->toBe('frontend');
});

test('ignores own entry but still detects other collisions when updating', function () {
    $tag = Tag::factory()->create(['name' => 'Design', 'slug' => 'design']);
    Tag::factory()->create(['slug' => 'design-2']);

    // Rename to something that collides with design-2
    $slug = app(SlugGenerator::class)->generate('Design 2', $tag->id);

    expect($slug)->toBe('design-2-2');
});
