<?php

namespace App\DataTransferObjects;

readonly class ExportOptionsData
{
    public function __construct(
        public string $locale,
        public ?array $tags = null,
        public bool $nested = true,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            locale: $data['locale'],
            tags: isset($data['tags']) ? (array) $data['tags'] : null,
            nested: $data['nested'] ?? true,
        );
    }

    public function getCacheKey(): string
    {
        $tagsPart = $this->tags ? implode(',', $this->tags) : 'all';
        $nestedPart = $this->nested ? 'nested' : 'flat';
        
        return "translations:export:{$this->locale}:{$tagsPart}:{$nestedPart}";
    }
}
