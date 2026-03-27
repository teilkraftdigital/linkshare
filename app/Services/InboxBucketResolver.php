<?php

namespace App\Services;

use App\Models\Bucket;

class InboxBucketResolver
{
    public function resolve(): Bucket
    {
        return Bucket::firstOrCreate(
            ['is_inbox' => true],
            ['name' => 'Inbox', 'color' => 'gray'],
        );
    }
}
