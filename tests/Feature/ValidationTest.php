<?php

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
});

describe('StoreTranslationRequest Validation', function () {
    test('rejects request when key is missing', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'locale' => 'en',
                'content' => 'Test content',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    });

    test('rejects request when key exceeds 255 characters', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => str_repeat('a', 256),
                'locale' => 'en',
                'content' => 'Test content',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    });

    test('rejects request when locale is missing', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'content' => 'Test content',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    });

    test('rejects request when locale is not exactly 2 characters', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'eng',
                'content' => 'Test content',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'e',
                'content' => 'Test content',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    });

    test('rejects request when content is missing', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    });

    test('rejects request when tags is not an array', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
                'tags' => 'not-an-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    });

    test('rejects request when tag exceeds 50 characters', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
                'tags' => [str_repeat('a', 51)],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    });

    test('accepts valid request with all required fields', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
            ]);

        $response->assertStatus(201);
    });

    test('accepts valid request with tags', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
                'tags' => ['web', 'mobile'],
            ]);

        $response->assertStatus(201);
    });
});

describe('UpdateTranslationRequest Validation', function () {
    test('rejects request when key exceeds 255 characters', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'key' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    });

    test('rejects request when locale is not exactly 2 characters', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'locale' => 'eng',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    });

    test('rejects request when tags is not an array', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'tags' => 'not-an-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    });

    test('rejects request when tag exceeds 50 characters', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'tags' => [str_repeat('a', 51)],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    });

    test('accepts valid partial update with key only', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'key' => 'updated.key',
            ]);

        $response->assertStatus(200);
    });

    test('accepts valid partial update with locale only', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'locale' => 'fr',
            ]);

        $response->assertStatus(200);
    });

    test('accepts valid partial update with content only', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200);
    });

    test('accepts valid partial update with tags only', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'tags' => ['web', 'mobile'],
            ]);

        $response->assertStatus(200);
    });

    test('accepts valid update with all fields', function () {
        $translation = Translation::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/translations/{$translation->id}", [
                'key' => 'updated.key',
                'locale' => 'fr',
                'content' => 'Updated content',
                'tags' => ['web'],
            ]);

        $response->assertStatus(200);
    });
});

describe('SearchTranslationRequest Validation', function () {
    test('rejects request when key exceeds 255 characters', function () {
        $response = $this->getJson('/api/translations/search?' . http_build_query([
            'key' => str_repeat('a', 256),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    });

    test('rejects request when locale is not exactly 2 characters', function () {
        $response = $this->getJson('/api/translations/search?' . http_build_query([
            'locale' => 'eng',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    });

    test('rejects request when tags is not an array', function () {
        $response = $this->getJson('/api/translations/search?tags=not-an-array');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    });

    test('rejects request when tag exceeds 50 characters', function () {
        $response = $this->getJson('/api/translations/search?' . http_build_query([
            'tags' => [str_repeat('a', 51)],
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0']);
    });

    test('rejects request when per_page is less than 1', function () {
        $response = $this->getJson('/api/translations/search?per_page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    });

    test('rejects request when per_page exceeds 100', function () {
        $response = $this->getJson('/api/translations/search?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    });

    test('accepts valid search with key parameter', function () {
        $response = $this->getJson('/api/translations/search?key=test');

        $response->assertStatus(200);
    });

    test('accepts valid search with locale parameter', function () {
        $response = $this->getJson('/api/translations/search?locale=en');

        $response->assertStatus(200);
    });

    test('accepts valid search with tags parameter', function () {
        $response = $this->getJson('/api/translations/search?' . http_build_query([
            'tags' => ['web', 'mobile'],
        ]));

        $response->assertStatus(200);
    });

    test('accepts valid search with content parameter', function () {
        $response = $this->getJson('/api/translations/search?content=test');

        $response->assertStatus(200);
    });

    test('accepts valid search with per_page parameter', function () {
        $response = $this->getJson('/api/translations/search?per_page=25');

        $response->assertStatus(200);
    });

    test('accepts valid search with all parameters', function () {
        $response = $this->getJson('/api/translations/search?' . http_build_query([
            'key' => 'test',
            'locale' => 'en',
            'tags' => ['web'],
            'content' => 'content',
            'per_page' => 50,
        ]));

        $response->assertStatus(200);
    });
});
