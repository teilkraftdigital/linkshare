<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->actingAs(User::factory()->create());
});

test('endpoint returns title and description as json', function () {
    Http::fake(['*' => Http::response(
        '<html><head><title>Example</title><meta name="description" content="Desc" /></head></html>',
    )]);

    $this->postJson(route('dashboard.links.fetch-meta'), ['url' => 'https://example.com'])
        ->assertOk()
        ->assertJson(['title' => 'Example', 'description' => 'Desc']);
});

test('endpoint returns nulls when meta is missing', function () {
    Http::fake(['*' => Http::response('<html><head></head></html>')]);

    $this->postJson(route('dashboard.links.fetch-meta'), ['url' => 'https://example.com'])
        ->assertOk()
        ->assertJson(['title' => null, 'description' => null]);
});

test('endpoint requires authentication', function () {
    auth()->logout();

    $this->postJson(route('dashboard.links.fetch-meta'), ['url' => 'https://example.com'])
        ->assertUnauthorized();
});

test('endpoint validates url field', function () {
    $this->postJson(route('dashboard.links.fetch-meta'), ['url' => 'not-a-url'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url']);
});
