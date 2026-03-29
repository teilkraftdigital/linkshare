<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LinkQueryBuilder
{
    public const PER_PAGE = 25;

    /**
     * @param  array{bucket_id?: int|string|null, tag_id?: int|string|null, search?: string|null}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Link::with(['bucket', 'tags'])->orderByDesc('id');

        if (! empty($filters['bucket_id'])) {
            $query->where('bucket_id', $filters['bucket_id']);
        }

        if (! empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters['tag_id']));
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('url', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }
}
