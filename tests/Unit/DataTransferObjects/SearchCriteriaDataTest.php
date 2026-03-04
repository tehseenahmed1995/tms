<?php

use App\DataTransferObjects\SearchCriteriaData;

test('creates SearchCriteriaData from request array with all filters', function () {
    $data = [
        'key' => 'auth',
        'locale' => 'en',
        'tags' => ['web', 'mobile'],
        'content' => 'login',
        'per_page' => 20,
    ];

    $dto = SearchCriteriaData::fromRequest($data);

    expect($dto->key)->toBe('auth')
        ->and($dto->locale)->toBe('en')
        ->and($dto->tags)->toBe(['web', 'mobile'])
        ->and($dto->content)->toBe('login')
        ->and($dto->perPage)->toBe(20);
});

test('creates SearchCriteriaData with default values when not provided', function () {
    $data = [];

    $dto = SearchCriteriaData::fromRequest($data);

    expect($dto->key)->toBeNull()
        ->and($dto->locale)->toBeNull()
        ->and($dto->tags)->toBeNull()
        ->and($dto->content)->toBeNull()
        ->and($dto->perPage)->toBe(15);
});

test('hasFilters returns true when filters are present', function () {
    $dto = new SearchCriteriaData(key: 'auth');

    expect($dto->hasFilters())->toBeTrue();
});

test('hasFilters returns false when no filters are present', function () {
    $dto = new SearchCriteriaData();

    expect($dto->hasFilters())->toBeFalse();
});
