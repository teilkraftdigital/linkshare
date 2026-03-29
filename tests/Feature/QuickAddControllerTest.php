<?php

use App\Models\Bucket;
use App\Models\User;

beforeEach(function () {
    Bucket::factory()->inbox()->create();
});

test('quick-add page renders for authenticated user', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard.quick-add'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/QuickAdd')
            ->has('buckets')
            ->has('tags')
            ->has('inboxBucketId')
            ->where('prefillUrl', '')
            ->where('prefillTitle', '')
        );
});

test('prefills url and title from query parameters', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard.quick-add', ['url' => 'https://example.com', 'title' => 'Example']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('prefillUrl', 'https://example.com')
            ->where('prefillTitle', 'Example')
        );
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('dashboard.quick-add', ['url' => 'https://example.com', 'title' => 'Example']))
        ->assertRedirect(route('login'));
});

test('intended url is preserved after login redirect', function () {
    $target = route('dashboard.quick-add', ['url' => 'https://example.com', 'title' => 'Example']);

    $this->get($target);

    $this->assertStringContainsString(
        'dashboard/quick-add',
        session()->get('url.intended', ''),
    );
});
