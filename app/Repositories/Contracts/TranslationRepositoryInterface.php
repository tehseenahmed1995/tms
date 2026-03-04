<?php

namespace App\Repositories\Contracts;

use App\Models\Translation;
use App\DataTransferObjects\SearchCriteriaData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TranslationRepositoryInterface
{
    public function create(array $data, array $tags): Translation;
    
    public function update(Translation $translation, array $data, array $tags): Translation;
    
    public function delete(Translation $translation): bool;
    
    public function findById(int $id): ?Translation;
    
    public function search(SearchCriteriaData $criteria): LengthAwarePaginator;
    
    public function getByLocale(string $locale, ?array $tags = null): Collection;
    
    public function getAllTags(): Collection;
}
