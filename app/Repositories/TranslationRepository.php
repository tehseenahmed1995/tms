<?php

namespace App\Repositories;

use App\Models\Translation;
use App\Models\Tag;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use App\DataTransferObjects\SearchCriteriaData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TranslationRepository implements TranslationRepositoryInterface
{
    public function create(array $data, array $tags): Translation
    {
        return DB::transaction(function () use ($data, $tags) {
            $translation = Translation::create($data);
            
            if (!empty($tags)) {
                $tagIds = $this->getOrCreateTags($tags);
                $translation->tags()->attach($tagIds);
            }
            
            return $translation->load('tags');
        });
    }

    public function update(Translation $translation, array $data, array $tags): Translation
    {
        return DB::transaction(function () use ($translation, $data, $tags) {
            $translation->update($data);
            
            $tagIds = $this->getOrCreateTags($tags);
            $translation->tags()->sync($tagIds);
            
            return $translation->fresh(['tags']);
        });
    }

    public function delete(Translation $translation): bool
    {
        return DB::transaction(function () use ($translation) {
            $translation->tags()->detach();
            return $translation->delete();
        });
    }

    public function findById(int $id): ?Translation
    {
        return Translation::with('tags')->find($id);
    }

    public function search(SearchCriteriaData $criteria): LengthAwarePaginator
    {
        $query = Translation::query()->with('tags');
        $likeOperator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        if ($criteria->key !== null) {
            $query->where('key', $likeOperator, "%{$criteria->key}%");
        }

        if ($criteria->locale !== null) {
            $query->where('locale', $criteria->locale);
        }

        if ($criteria->content !== null) {
            $query->where('content', $likeOperator, "%{$criteria->content}%");
        }

        if ($criteria->tags !== null && !empty($criteria->tags)) {
            $query->whereHas('tags', function ($q) use ($criteria) {
                $q->whereIn('name', $criteria->tags);
            }, '=', count($criteria->tags));
        }

        return $query->paginate($criteria->perPage);
    }

    public function getByLocale(string $locale, ?array $tags = null): Collection
    {
        $query = Translation::query()
            ->select(['key', 'content'])
            ->where('locale', $locale);

        if ($tags !== null && !empty($tags)) {
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        return $query->get();
    }

    public function getAllTags(): Collection
    {
        return Tag::orderBy('name')->get();
    }

    private function getOrCreateTags(array $tagNames): array
    {
        if (empty($tagNames)) {
            return [];
        }

        $existingTags = Tag::whereIn('name', $tagNames)->pluck('id', 'name');
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            if (isset($existingTags[$tagName])) {
                $tagIds[] = $existingTags[$tagName];
            } else {
                $tag = Tag::create(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
        }

        return $tagIds;
    }
}
