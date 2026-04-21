<?php

use App\Models\Bucket;
use App\Models\User;
use App\Services\Import\BucketMerger;
use App\Services\Import\ImportOptions;

beforeEach(function () {
    User::factory()->create();
    Bucket::factory()->inbox()->create();
    $this->merger = app(BucketMerger::class);
});

test('creates bucket that does not exist', function () {
    $result = $this->merger->merge(
        [['name' => 'Work', 'color' => 'blue', 'is_inbox' => false]],
        ImportOptions::all(),
    );

    expect($result['created'])->toBe(1);
    expect($result['map'])->toHaveKey('work');
    expect(Bucket::where('name', 'Work')->exists())->toBeTrue();
});

test('reuses existing bucket by name (case-insensitive)', function () {
    Bucket::factory()->create(['name' => 'Work', 'color' => 'red']);

    $result = $this->merger->merge(
        [['name' => 'work', 'color' => 'blue', 'is_inbox' => false]],
        ImportOptions::all(),
    );

    expect($result['created'])->toBe(0);
    expect(Bucket::where('name', 'Work')->count())->toBe(1);
});

test('skips buckets not in allowed names', function () {
    $result = $this->merger->merge(
        [
            ['name' => 'Work', 'color' => 'blue', 'is_inbox' => false],
            ['name' => 'Personal', 'color' => 'green', 'is_inbox' => false],
        ],
        ImportOptions::selected(['Work'], []),
    );

    expect($result['created'])->toBe(1);
    expect($result['map'])->toHaveKey('work');
    expect($result['map'])->not->toHaveKey('personal');
});

test('skips buckets with empty name', function () {
    $result = $this->merger->merge(
        [['name' => '', 'color' => 'blue', 'is_inbox' => false]],
        ImportOptions::all(),
    );

    expect($result['created'])->toBe(0);
    expect($result['map'])->toBeEmpty();
});
