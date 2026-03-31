<?php

namespace App\Observers;

use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

class TagObserver
{
    public function created(Tag $tag): void
    {
        Cache::forget('dashboard');
    }

    public function updated(Tag $tag): void
    {
        Cache::forget('dashboard');
    }

    public function deleted(Tag $tag): void
    {
        Cache::forget('dashboard');
    }

    public function restored(Tag $tag): void
    {
        Cache::forget('dashboard');
    }

    public function forceDeleted(Tag $tag): void
    {
        Cache::forget('dashboard');
    }
}
