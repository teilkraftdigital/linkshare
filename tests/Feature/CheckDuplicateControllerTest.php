<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('returns exists false and similar false for unknown url', function () {
    $this->postJson(route('dashboard.links.check-duplicate'), ['url' => 'https://example.com'])
        ->assertOk()
        ->assertJson(['exists' => false, 'similar' => false]);
});

test('returns exists true for exact url match', function () {
    Link::factory()->create(['url' => 'https://example.com', 'bucket_id' => $this->inbox->id]);

    $this->postJson(route('dashboard.links.check-duplicate'), ['url' => 'https://example.com'])
        ->assertOk()
        ->assertJson(['exists' => true, 'similar' => false]);
});

test('returns exists true for url matching after trailing slash normalization', function () {
    Link::factory()->create(['url' => 'https://example.com', 'bucket_id' => $this->inbox->id]);

    $this->postJson(route('dashboard.links.check-duplicate'), ['url' => 'https://example.com/'])
        ->assertOk()
        ->assertJson(['exists' => true, 'similar' => false]);
});

test('returns similar true for same base url with different query string', function () {
    Link::factory()->create(['url' => 'https://example.com/page?ref=old', 'bucket_id' => $this->inbox->id]);

    $this->postJson(route('dashboard.links.check-duplicate'), ['url' => 'https://example.com/page?ref=new'])
        ->assertOk()
        ->assertJson(['exists' => false, 'similar' => true]);
});

test('endpoint is behind auth middleware', function () {
    auth()->logout();

    $this->postJson(route('dashboard.links.check-duplicate'), ['url' => 'https://example.com'])
        ->assertUnauthorized();
});

test('validates url field', function () {
    $this->postJson(route('dashboard.links.check-duplicate'), ['url' => 'not-a-url'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url']);
});
