<?php

namespace App\Services\Import;

use App\Models\Tag;
use App\Services\SlugGenerator;

final class TagMerger
{
    public function __construct(private readonly SlugGenerator $slugGenerator) {}

    /**
     * Find-or-create tags from export data, filtered by options.
     *
     * @param  list<array{name: string, color: string, description: string|null, is_public: bool}>  $tagData
     * @return array{map: array<string, Tag>, created: int}
     */
    public function merge(array $tagData, ImportOptions $options): array
    {
        $map = [];
        $created = 0;

        foreach ($tagData as $t) {
            $name = $t['name'] ?? '';

            if ($name === '' || ! $options->allowsTag($name)) {
                continue;
            }

            $nameLower = mb_strtolower($name);
            $existing = Tag::whereRaw('LOWER(name) = ?', [$nameLower])->withTrashed()->first();

            if ($existing) {
                $map[$nameLower] = $existing;
            } else {
                $map[$nameLower] = Tag::create([
                    'name' => $name,
                    'slug' => $this->slugGenerator->generate($name),
                    'color' => $t['color'] ?? 'gray',
                    'description' => $t['description'] ?? null,
                    'is_public' => (bool) ($t['is_public'] ?? false),
                ]);
                $created++;
            }
        }

        return ['map' => $map, 'created' => $created];
    }
}
