<?php

use App\Services\Import\ImportOptions;

test('all() allows any bucket name', function () {
    $options = ImportOptions::all();

    expect($options->allowsBucket('Work'))->toBeTrue();
    expect($options->allowsBucket('anything'))->toBeTrue();
});

test('all() allows any tag name', function () {
    $options = ImportOptions::all();

    expect($options->allowsTag('php'))->toBeTrue();
    expect($options->allowsTag('anything'))->toBeTrue();
});

test('all() is not selective on buckets', function () {
    expect(ImportOptions::all()->isSelectiveOnBuckets())->toBeFalse();
});

test('selected() allows only specified bucket names', function () {
    $options = ImportOptions::selected(['Work', 'Personal'], []);

    expect($options->allowsBucket('Work'))->toBeTrue();
    expect($options->allowsBucket('Personal'))->toBeTrue();
    expect($options->allowsBucket('Archive'))->toBeFalse();
});

test('selected() allows only specified tag names', function () {
    $options = ImportOptions::selected([], ['php', 'design']);

    expect($options->allowsTag('php'))->toBeTrue();
    expect($options->allowsTag('design'))->toBeTrue();
    expect($options->allowsTag('other'))->toBeFalse();
});

test('selected() matching is case-insensitive', function () {
    $options = ImportOptions::selected(['Work'], ['PHP']);

    expect($options->allowsBucket('work'))->toBeTrue();
    expect($options->allowsBucket('WORK'))->toBeTrue();
    expect($options->allowsTag('php'))->toBeTrue();
    expect($options->allowsTag('Php'))->toBeTrue();
});

test('selected() is selective on buckets', function () {
    expect(ImportOptions::selected(['Work'], [])->isSelectiveOnBuckets())->toBeTrue();
});

test('selected() with empty bucket names is not selective on buckets', function () {
    expect(ImportOptions::selected([], ['php'])->isSelectiveOnBuckets())->toBeFalse();
});
