<?php

namespace App\Services\Import;

use App\Models\Bucket;

final class BucketMerger
{
    /**
     * Find-or-create buckets from export data, filtered by options.
     *
     * @param  list<array{name: string, color: string, is_inbox: bool}>  $bucketData
     * @return array{map: array<string, Bucket>, created: int}
     */
    public function merge(array $bucketData, ImportOptions $options): array
    {
        $map = [];
        $created = 0;

        foreach ($bucketData as $b) {
            $name = $b['name'] ?? '';

            if ($name === '' || ! $options->allowsBucket($name)) {
                continue;
            }

            $nameLower = mb_strtolower($name);
            $existing = Bucket::whereRaw('LOWER(name) = ?', [$nameLower])->first();

            if ($existing) {
                $map[$nameLower] = $existing;
            } else {
                $map[$nameLower] = Bucket::create([
                    'name' => $name,
                    'color' => $b['color'] ?? 'gray',
                    'is_inbox' => (bool) ($b['is_inbox'] ?? false),
                ]);
                $created++;
            }
        }

        return ['map' => $map, 'created' => $created];
    }
}
