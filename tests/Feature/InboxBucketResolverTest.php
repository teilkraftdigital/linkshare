<?php

use App\Models\Bucket;
use App\Services\InboxBucketResolver;

test('InboxBucketResolver creates inbox bucket when none exists', function () {
    $resolver = app(InboxBucketResolver::class);

    $inbox = $resolver->resolve();

    expect($inbox->is_inbox)->toBeTrue();
    expect($inbox->name)->toBe('Inbox');
    expect(Bucket::where('is_inbox', true)->count())->toBe(1);
});

test('InboxBucketResolver returns existing inbox bucket', function () {
    $existing = Bucket::factory()->inbox()->create();

    $resolver = app(InboxBucketResolver::class);
    $inbox = $resolver->resolve();

    expect($inbox->id)->toBe($existing->id);
    expect(Bucket::where('is_inbox', true)->count())->toBe(1);
});
