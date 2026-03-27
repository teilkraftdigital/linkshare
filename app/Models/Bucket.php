<?php

namespace App\Models;

use Database\Factories\BucketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'color', 'is_inbox'])]
class Bucket extends Model
{
    /** @use HasFactory<BucketFactory> */
    use HasFactory, SoftDeletes;
}
