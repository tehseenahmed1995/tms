<?php

use App\Services\TranslationService;
use App\Services\CacheInvalidationService;
use App\Repositories\TranslationRepository;
use App\DataTransferObjects\TranslationData;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('throws exception when creating duplicate translation', function () {
    $repository = new TranslationRepository();
    $cacheInvalidation = new CacheInvalidationService();
    $service = new TranslationService($repository, $cacheInvalidation);

    Translation::factory()->create([
        'key' => 'auth.login.title',
        'locale' => 'en',
    ]);

    $data = new TranslationData(
        key: 'auth.login.title',
        locale: 'en',
        content: 'Login',
        tags: []
    );

    $service->createTranslation($data);
})->throws(\InvalidArgumentException::class, "Translation with key 'auth.login.title' already exists for locale 'en'");

test('creates translation successfully when no duplicate exists', function () {
    $repository = new TranslationRepository();
    $cacheInvalidation = new CacheInvalidationService();
    $service = new TranslationService($repository, $cacheInvalidation);

    $data = new TranslationData(
        key: 'auth.login.title',
        locale: 'en',
        content: 'Login',
        tags: ['web']
    );

    $result = $service->createTranslation($data);

    expect($result)->toBeInstanceOf(Translation::class)
        ->and($result->key)->toBe('auth.login.title')
        ->and($result->locale)->toBe('en')
        ->and($result->content)->toBe('Login')
        ->and($result->tags)->toHaveCount(1);
});
