<?php

namespace App\Services\Import;

final class ImportOptions
{
    /** @param list<string> $allowedBucketNames  Empty = all buckets allowed */
    /** @param list<string> $allowedTagNames     Empty = all tags allowed */
    private function __construct(
        private readonly array $allowedBucketNames,
        private readonly array $allowedTagNames,
    ) {}

    public static function all(): self
    {
        return new self([], []);
    }

    /** @param list<string> $bucketNames @param list<string> $tagNames */
    public static function selected(array $bucketNames, array $tagNames): self
    {
        return new self($bucketNames, $tagNames);
    }

    public function allowsBucket(string $name): bool
    {
        if ($this->allowedBucketNames === []) {
            return true;
        }

        return in_array(mb_strtolower($name), array_map('mb_strtolower', $this->allowedBucketNames), true);
    }

    public function allowsTag(string $name): bool
    {
        if ($this->allowedTagNames === []) {
            return true;
        }

        return in_array(mb_strtolower($name), array_map('mb_strtolower', $this->allowedTagNames), true);
    }

    /** When true, links whose bucket was excluded should be skipped rather than moved to inbox. */
    public function isSelectiveOnBuckets(): bool
    {
        return $this->allowedBucketNames !== [];
    }
}
