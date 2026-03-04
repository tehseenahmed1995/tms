<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    public function invalidateForTranslation(Translation $translation): void
    {
        $locale = $translation->locale;
        $tags = $translation->tags->pluck('name')->toArray();

        // Invalidate all export cache for this locale
        $this->invalidateExportCache($locale);

        // Invalidate tag-specific caches
        foreach ($tags as $tag) {
            $this->invalidateExportCache($locale, [$tag]);
        }
    }

    public function invalidateExportCache(string $locale, ?array $tags = null): void
    {
        $patterns = [
            "translations:export:{$locale}:all:nested",
            "translations:export:{$locale}:all:flat",
        ];

        if ($tags !== null) {
            $tagsPart = implode(',', $tags);
            $patterns[] = "translations:export:{$locale}:{$tagsPart}:nested";
            $patterns[] = "translations:export:{$locale}:{$tagsPart}:flat";
        }

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    public function invalidateAll(): void
    {
        Cache::flush();
    }
}
