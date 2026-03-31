<?php

namespace App\Observers;

use App\Models\Bucket;
use Illuminate\Support\Facades\Cache;

class BucketObserver
{
    public function created(Bucket $bucket): void
    {
        Cache::forget('dashboard');
    }

    public function updated(Bucket $bucket): void
    {
        Cache::forget('dashboard');
    }

    public function deleted(Bucket $bucket): void
    {
        Cache::forget('dashboard');
    }

    public function restored(Bucket $bucket): void
    {
        Cache::forget('dashboard');
    }

    public function forceDeleted(Bucket $bucket): void
    {
        Cache::forget('dashboard');
    }
}
