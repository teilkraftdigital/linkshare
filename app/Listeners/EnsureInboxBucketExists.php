<?php

namespace App\Listeners;

use App\Events\AdminCreated;
use App\Services\InboxBucketResolver;

class EnsureInboxBucketExists
{
    public function __construct(private readonly InboxBucketResolver $inboxBucketResolver) {}

    public function handle(AdminCreated $event): void
    {
        $this->inboxBucketResolver->resolve();
    }
}
