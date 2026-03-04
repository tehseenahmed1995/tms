<?php

namespace App\Services;

use App\Repositories\Contracts\TranslationRepositoryInterface;
use App\DataTransferObjects\TranslationData;
use App\DataTransferObjects\SearchCriteriaData;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TranslationService
{
    public function __construct(
        private TranslationRepositoryInterface $repository,
        private CacheInvalidationService $cacheInvalidation,
    ) {}

    public function createTranslation(TranslationData $data): Translation
    {
        // Check for duplicate key+locale combination
        $existing = Translation::where('key', $data->key)
            ->where('locale', $data->locale)
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException(
                "Translation with key '{$data->key}' already exists for locale '{$data->locale}'"
            );
        }

        $translation = $this->repository->create(
            ['key' => $data->key, 'locale' => $data->locale, 'content' => $data->content],
            $data->tags
        );

        $this->cacheInvalidation->invalidateForTranslation($translation);

        return $translation;
    }

    public function updateTranslation(Translation $translation, TranslationData $data): Translation
    {
        $updated = $this->repository->update(
            $translation,
            ['key' => $data->key, 'locale' => $data->locale, 'content' => $data->content],
            $data->tags
        );

        $this->cacheInvalidation->invalidateForTranslation($updated);

        return $updated;
    }

    public function deleteTranslation(Translation $translation): bool
    {
        $this->cacheInvalidation->invalidateForTranslation($translation);
        
        return $this->repository->delete($translation);
    }

    public function getTranslation(int $id): ?Translation
    {
        return $this->repository->findById($id);
    }

    public function searchTranslations(SearchCriteriaData $criteria): LengthAwarePaginator
    {
        return $this->repository->search($criteria);
    }

    public function getAllTags(): array
    {
        return $this->repository->getAllTags()->pluck('name')->toArray();
    }
}
