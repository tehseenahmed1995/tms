<?php

namespace App\Services;

use App\Repositories\Contracts\TranslationRepositoryInterface;
use App\DataTransferObjects\ExportOptionsData;
use Illuminate\Support\Facades\Cache;

class TranslationExportService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private TranslationRepositoryInterface $repository,
    ) {}

    public function exportToJson(ExportOptionsData $options): array
    {
        $cacheKey = $options->getCacheKey();

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($options) {
            $translations = $this->repository->getByLocale($options->locale, $options->tags);

            if ($options->nested) {
                return $this->convertToNestedArray($translations);
            }

            return $translations->pluck('content', 'key')->toArray();
        });
    }

    public function getExportETag(ExportOptionsData $options): string
    {
        $data = $this->exportToJson($options);
        return md5(json_encode($data));
    }

    private function convertToNestedArray($translations): array
    {
        $result = [];

        foreach ($translations as $translation) {
            $keys = explode('.', $translation->key);
            $current = &$result;

            foreach ($keys as $index => $key) {
                if ($index === count($keys) - 1) {
                    $current[$key] = $translation->content;
                } else {
                    if (!isset($current[$key]) || !is_array($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }
            }
        }

        return $result;
    }
}
