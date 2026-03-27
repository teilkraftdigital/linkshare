<?php

use App\Models\Bucket;
use App\Models\User;

test('admin:create creates a user and inbox bucket', function () {
    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Admin User')
        ->expectsQuestion('E-Mail', 'admin@example.com')
        ->expectsQuestion('Password', 'password123')
        ->expectsOutput('Admin account created for admin@example.com.')
        ->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->exists())->toBeTrue();
    expect(Bucket::where('is_inbox', true)->exists())->toBeTrue();
});

test('admin:create fails with invalid email', function () {
    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Admin User')
        ->expectsQuestion('E-Mail', 'not-an-email')
        ->expectsQuestion('Password', 'password123')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('admin:create fails when email already exists', function () {
    User::factory()->create(['email' => 'admin@example.com']);

    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Admin User')
        ->expectsQuestion('E-Mail', 'admin@example.com')
        ->expectsQuestion('Password', 'password123')
        ->assertFailed();
});

test('admin:create does not store plaintext password', function () {
    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Admin User')
        ->expectsQuestion('E-Mail', 'admin@example.com')
        ->expectsQuestion('Password', 'password123')
        ->assertSuccessful();

    $user = User::where('email', 'admin@example.com')->first();

    expect($user->password)->not->toBe('password123');
});
