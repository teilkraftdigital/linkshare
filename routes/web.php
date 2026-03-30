<?php

use App\Http\Controllers\Dashboard\BucketController;
use App\Http\Controllers\Dashboard\CheckDuplicateController;
use App\Http\Controllers\Dashboard\ExportController;
use App\Http\Controllers\Dashboard\ImportController;
use App\Http\Controllers\Dashboard\LinkController;
use App\Http\Controllers\Dashboard\MetaFetchController;
use App\Http\Controllers\Dashboard\QuickAddController;
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
    Route::post('dashboard/buckets/{bucket}/restore', [BucketController::class, 'restore'])->withTrashed()->name('dashboard.buckets.restore');
    Route::delete('dashboard/buckets/{bucket}/force', [BucketController::class, 'forceDelete'])->withTrashed()->name('dashboard.buckets.force-delete');

    Route::get('dashboard/tags', [DashboardTagController::class, 'index'])->name('dashboard.tags.index');
    Route::post('dashboard/tags', [DashboardTagController::class, 'store'])->name('dashboard.tags.store');
    Route::patch('dashboard/tags/{tag}', [DashboardTagController::class, 'update'])->name('dashboard.tags.update');
    Route::delete('dashboard/tags/{tag}', [DashboardTagController::class, 'destroy'])->name('dashboard.tags.destroy');
    Route::post('dashboard/tags/{tag}/restore', [DashboardTagController::class, 'restore'])->withTrashed()->name('dashboard.tags.restore');
    Route::delete('dashboard/tags/{tag}/force', [DashboardTagController::class, 'forceDelete'])->withTrashed()->name('dashboard.tags.force-delete');

    Route::get('dashboard/quick-add', QuickAddController::class)->name('dashboard.quick-add');
    Route::post('dashboard/export', ExportController::class)->name('dashboard.export');
    Route::get('dashboard/import', [ImportController::class, 'create'])->name('dashboard.import.create');
    Route::post('dashboard/import', [ImportController::class, 'store'])->name('dashboard.import.store');

    Route::post('dashboard/links/fetch-meta', MetaFetchController::class)->name('dashboard.links.fetch-meta');
    Route::post('dashboard/links/check-duplicate', CheckDuplicateController::class)->name('dashboard.links.check-duplicate');

    Route::get('dashboard/links', [LinkController::class, 'index'])->name('dashboard.links.index');
    Route::post('dashboard/links', [LinkController::class, 'store'])->name('dashboard.links.store');
    Route::patch('dashboard/links/{link}', [LinkController::class, 'update'])->name('dashboard.links.update');
    Route::delete('dashboard/links/{link}', [LinkController::class, 'destroy'])->name('dashboard.links.destroy');
    Route::post('dashboard/links/{link}/refetch-meta', [LinkController::class, 'refetchMeta'])->name('dashboard.links.refetch-meta');
    Route::post('dashboard/links/{link}/restore', [LinkController::class, 'restore'])->withTrashed()->name('dashboard.links.restore');
    Route::delete('dashboard/links/{link}/force', [LinkController::class, 'forceDelete'])->withTrashed()->name('dashboard.links.force-delete');
});

Route::get('tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');

require __DIR__.'/settings.php';
