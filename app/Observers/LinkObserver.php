<?php

namespace App\Observers;

use App\Models\Link;
use Illuminate\Support\Facades\Cache;

class LinkObserver
{
    public function created(Link $link): void
    {
        Cache::forget('dashboard');
    }

    public function updated(Link $link): void
    {
        Cache::forget('dashboard');
    }

    public function deleted(Link $link): void
    {
        Cache::forget('dashboard');
    }

    public function restored(Link $link): void
    {
        Cache::forget('dashboard');
    }

    public function forceDeleted(Link $link): void
    {
        Cache::forget('dashboard');
    }
}
