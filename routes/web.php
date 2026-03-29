<?php

use App\Http\Controllers\Dashboard\BucketController;
use App\Http\Controllers\Dashboard\LinkController;
use App\Http\Controllers\Dashboard\TagController as DashboardTagController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard.index')
        : redirect()->route('login');
})->name('home');

// Placeholder until Issue #6 builds the full admin area
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => inertia('Dashboard'))->name('dashboard.index');

    Route::get('dashboard/buckets', [BucketController::class, 'index'])->name('dashboard.buckets.index');
    Route::post('dashboard/buckets', [BucketController::class, 'store'])->name('dashboard.buckets.store');
    Route::patch('dashboard/buckets/{bucket}', [BucketController::class, 'update'])->name('dashboard.buckets.update');
    Route::delete('dashboard/buckets/{bucket}', [BucketController::class, 'destroy'])->name('dashboard.buckets.destroy');

    Route::get('dashboard/tags', [DashboardTagController::class, 'index'])->name('dashboard.tags.index');
    Route::post('dashboard/tags', [DashboardTagController::class, 'store'])->name('dashboard.tags.store');
    Route::patch('dashboard/tags/{tag}', [DashboardTagController::class, 'update'])->name('dashboard.tags.update');
    Route::delete('dashboard/tags/{tag}', [DashboardTagController::class, 'destroy'])->name('dashboard.tags.destroy');

    Route::get('dashboard/links', [LinkController::class, 'index'])->name('dashboard.links.index');
    Route::post('dashboard/links', [LinkController::class, 'store'])->name('dashboard.links.store');
    Route::patch('dashboard/links/{link}', [LinkController::class, 'update'])->name('dashboard.links.update');
    Route::delete('dashboard/links/{link}', [LinkController::class, 'destroy'])->name('dashboard.links.destroy');
});

Route::get('tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');

require __DIR__.'/settings.php';
