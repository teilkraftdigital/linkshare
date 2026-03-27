<?php

use App\Models\User;

test('guest is redirected to login from /', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('authenticated user is redirected to admin links from /', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('admin.links.index'));
});

test('register page returns 404', function () {
    $this->get('/register')->assertNotFound();
});
