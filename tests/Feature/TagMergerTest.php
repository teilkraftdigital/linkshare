<?php

use App\Models\Tag;
use App\Models\User;
use App\Services\Import\ImportOptions;
use App\Services\Import\TagMerger;

beforeEach(function () {
    User::factory()->create();
    $this->merger = app(TagMerger::class);
});

test('creates tag that does not exist', function () {
    $result = $this->merger->merge(
        [['name' => 'php', 'color' => 'blue', 'description' => null, 'is_public' => false]],
        ImportOptions::all(),
    );

    expect($result['created'])->toBe(1);
    expect($result['map'])->toHaveKey('php');
    expect(Tag::where('name', 'php')->exists())->toBeTrue();
});

test('created tag gets a slug', function () {
    $this->merger->merge(
        [['name' => 'My Tag', 'color' => 'blue', 'description' => null, 'is_public' => false]],
        ImportOptions::all(),
    );

    expect(Tag::where('name', 'My Tag')->first()->slug)->toBe('my-tag');
});

test('reuses existing tag by name (case-insensitive), including soft-deleted', function () {
    $tag = Tag::factory()->create(['name' => 'php', 'slug' => 'php']);
    $tag->delete();

    $result = $this->merger->merge(
        [['name' => 'PHP', 'color' => 'blue', 'description' => null, 'is_public' => false]],
        ImportOptions::all(),
    );

    expect($result['created'])->toBe(0);
    expect(Tag::withTrashed()->where('name', 'php')->count())->toBe(1);
});

test('skips tags not in allowed names', function () {
    $result = $this->merger->merge(
        [
            ['name' => 'php', 'color' => 'blue', 'description' => null, 'is_public' => false],
            ['name' => 'design', 'color' => 'red', 'description' => null, 'is_public' => false],
        ],
        ImportOptions::selected([], ['php']),
    );

    expect($result['created'])->toBe(1);
    expect($result['map'])->toHaveKey('php');
    expect($result['map'])->not->toHaveKey('design');
});

test('skips tags with empty name', function () {
    $result = $this->merger->merge(
        [['name' => '', 'color' => 'blue', 'description' => null, 'is_public' => false]],
        ImportOptions::all(),
    );

    expect($result['created'])->toBe(0);
});
