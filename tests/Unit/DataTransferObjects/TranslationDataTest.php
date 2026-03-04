<?php

use App\DataTransferObjects\TranslationData;

test('creates TranslationData from request array', function () {
    $data = [
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
        'tags' => ['web', 'mobile'],
    ];

    $dto = TranslationData::fromRequest($data);

    expect($dto->key)->toBe('auth.login.title')
        ->and($dto->locale)->toBe('en')
        ->and($dto->content)->toBe('Login')
        ->and($dto->tags)->toBe(['web', 'mobile'])
        ->and($dto->id)->toBeNull();
});

test('creates TranslationData with empty tags when not provided', function () {
    $data = [
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
    ];

    $dto = TranslationData::fromRequest($data);

    expect($dto->tags)->toBe([]);
});

test('converts TranslationData to array', function () {
    $dto = new TranslationData(
        key: 'auth.login.title',
        locale: 'en',
        content: 'Login',
        tags: ['web', 'mobile'],
        id: 1
    );

    $array = $dto->toArray();

    expect($array)->toBe([
        'key' => 'auth.login.title',
        'locale' => 'en',
        'content' => 'Login',
        'tags' => ['web', 'mobile'],
    ]);
});
