<?php

namespace App\DataTransferObjects;

readonly class TranslationData
{
    public function __construct(
        public string $key,
        public string $locale,
        public string $content,
        public array $tags = [],
        public ?int $id = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            key: $data['key'],
            locale: $data['locale'],
            content: $data['content'],
            tags: $data['tags'] ?? [],
            id: $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'locale' => $this->locale,
            'content' => $this->content,
            'tags' => $this->tags,
        ];
    }
}
