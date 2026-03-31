<?php

namespace App\Models;

use App\Observers\BucketObserver;
use Database\Factories\BucketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'color', 'is_inbox'])]
#[ObservedBy(BucketObserver::class)]
class Bucket extends Model
{
    /** @use HasFactory<BucketFactory> */
    use HasFactory, SoftDeletes;

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }
}
