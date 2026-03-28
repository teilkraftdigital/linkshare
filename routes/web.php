<?php

use App\Http\Controllers\Dashboard\BucketController;
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
});

require __DIR__.'/settings.php';
