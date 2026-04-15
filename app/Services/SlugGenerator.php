<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Generate a unique slug for a tag, scoped to the given parent.
     * Root tags (parentId = null) are unique globally among root tags.
     * Child tags are unique within their parent's scope.
     */
    public function generate(string $name, ?int $ignoreId = null, ?int $parentId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            Tag::withTrashed()
                ->where('slug', $slug)
                ->where('parent_id', $parentId)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
