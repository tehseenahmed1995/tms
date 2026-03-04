<?php

use App\DataTransferObjects\ExportOptionsData;

test('creates ExportOptionsData from request array', function () {
    $data = [
        'locale' => 'en',
        'tags' => ['web', 'mobile'],
        'nested' => true,
    ];

    $dto = ExportOptionsData::fromRequest($data);

    expect($dto->locale)->toBe('en')
        ->and($dto->tags)->toBe(['web', 'mobile'])
        ->and($dto->nested)->toBeTrue();
});

test('creates ExportOptionsData with default nested value', function () {
    $data = [
        'locale' => 'en',
    ];

    $dto = ExportOptionsData::fromRequest($data);

    expect($dto->nested)->toBeTrue()
        ->and($dto->tags)->toBeNull();
});

test('generates cache key with tags', function () {
    $dto = new ExportOptionsData(
        locale: 'en',
        tags: ['web', 'mobile'],
        nested: true
    );

    $cacheKey = $dto->getCacheKey();

    expect($cacheKey)->toBe('translations:export:en:web,mobile:nested');
});

test('generates cache key without tags', function () {
    $dto = new ExportOptionsData(
        locale: 'en',
        tags: null,
        nested: false
    );

    $cacheKey = $dto->getCacheKey();

    expect($cacheKey)->toBe('translations:export:en:all:flat');
});
