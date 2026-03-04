<?php

use App\Services\TranslationService;
use App\Services\CacheInvalidationService;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use App\DataTransferObjects\TranslationData;
use App\DataTransferObjects\SearchCriteriaData;
use App\Models\Translation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = Mockery::mock(TranslationRepositoryInterface::class);
    $this->cacheInvalidation = Mockery::mock(CacheInvalidationService::class);
    $this->service = new TranslationService($this->repository, $this->cacheInvalidation);
});

afterEach(function () {
    Mockery::close();
});

test('updates translation and invalidates cache', function () {
    $translation = new Translation([
        'id' => 1,
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
    ]);

    $data = new TranslationData(
        key: 'auth.logout.title',
        locale: 'fr',
        content: 'Déconnexion',
        tags: ['mobile']
    );

    $updated = new Translation([
        'id' => 1,
        'key' => 'auth.logout.title',
        'locale' => 'fr',
        'content' => 'Déconnexion',
    ]);

    $this->repository->shouldReceive('update')
        ->once()
        ->with($translation, ['key' => 'auth.logout.title', 'locale' => 'fr', 'content' => 'Déconnexion'], ['mobile'])
        ->andReturn($updated);

    $this->cacheInvalidation->shouldReceive('invalidateForTranslation')
        ->once()
        ->with($updated);

    $result = $this->service->updateTranslation($translation, $data);

    expect($result)->toBe($updated);
});

test('deletes translation and invalidates cache', function () {
    $translation = new Translation([
        'id' => 1,
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
    ]);

    $this->cacheInvalidation->shouldReceive('invalidateForTranslation')
        ->once()
        ->with($translation);

    $this->repository->shouldReceive('delete')
        ->once()
        ->with($translation)
        ->andReturn(true);

    $result = $this->service->deleteTranslation($translation);

    expect($result)->toBeTrue();
});

test('gets translation by id', function () {
    $translation = new Translation([
        'id' => 1,
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
    ]);

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($translation);

    $result = $this->service->getTranslation(1);

    expect($result)->toBe($translation);
});

test('searches translations', function () {
    $criteria = new SearchCriteriaData(key: 'auth');
    $paginator = Mockery::mock(LengthAwarePaginator::class);

    $this->repository->shouldReceive('search')
        ->once()
        ->with($criteria)
        ->andReturn($paginator);

    $result = $this->service->searchTranslations($criteria);

    expect($result)->toBe($paginator);
});

test('gets all tags', function () {
    $tags = new \Illuminate\Database\Eloquent\Collection([
        (object)['name' => 'web'],
        (object)['name' => 'mobile'],
    ]);

    $this->repository->shouldReceive('getAllTags')
        ->once()
        ->andReturn($tags);

    $result = $this->service->getAllTags();

    expect($result)->toBe(['web', 'mobile']);
});
