<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.links.index')
        : redirect()->route('login');
})->name('home');

// Placeholder until Issue #6 builds the full admin area
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin/links', fn () => inertia('Dashboard'))->name('admin.links.index');
});

require __DIR__.'/settings.php';
