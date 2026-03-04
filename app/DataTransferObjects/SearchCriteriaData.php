<?php

namespace App\DataTransferObjects;

readonly class SearchCriteriaData
{
    public function __construct(
        public ?string $key = null,
        public ?string $locale = null,
        public ?array $tags = null,
        public ?string $content = null,
        public int $perPage = 15,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            key: $data['key'] ?? null,
            locale: $data['locale'] ?? null,
            tags: isset($data['tags']) ? (array) $data['tags'] : null,
            content: $data['content'] ?? null,
            perPage: $data['per_page'] ?? 15,
        );
    }

    public function hasFilters(): bool
    {
        return $this->key !== null
            || $this->locale !== null
            || $this->tags !== null
            || $this->content !== null;
    }
}
