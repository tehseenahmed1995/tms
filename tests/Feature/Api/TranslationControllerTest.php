<?php

namespace Tests\Feature\Api;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_translations_with_collection(): void
    {
        // Create multiple translations
        Translation::create([
            'key' => 'test.key1',
            'locale' => 'en',
            'content' => 'Content 1',
        ]);

        Translation::create([
            'key' => 'test.key2',
            'locale' => 'en',
            'content' => 'Content 2',
        ]);

        Translation::create([
            'key' => 'test.key3',
            'locale' => 'en',
            'content' => 'Content 3',
        ]);

        // Make request
        $response = $this->getJson('/api/translations');

        // Assert response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'locale',
                        'content',
                        'tags',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);

        // Assert meta values
        $response->assertJson([
            'meta' => [
                'total' => 3,
                'per_page' => 15,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 3,
            ],
        ]);

        // Assert data count
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_returns_empty_collection_when_no_translations(): void
    {
        // Make request
        $response = $this->getJson('/api/translations');

        // Assert response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);

        // Assert empty data
        $this->assertCount(0, $response->json('data'));

        // Assert meta values for empty results
        $response->assertJson([
            'meta' => [
                'total' => 0,
                'per_page' => 15,
                'current_page' => 1,
                'last_page' => 1,
                'from' => null,
                'to' => null,
            ],
        ]);
    }

    public function test_index_paginates_correctly(): void
    {
        // Create 20 translations
        for ($i = 1; $i <= 20; $i++) {
            Translation::create([
                'key' => "test.key{$i}",
                'locale' => 'en',
                'content' => "Content {$i}",
            ]);
        }

        // Request first page
        $response = $this->getJson('/api/translations?page=1');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'total' => 20,
                    'per_page' => 15,
                    'current_page' => 1,
                    'last_page' => 2,
                    'from' => 1,
                    'to' => 15,
                ],
            ]);

        $this->assertCount(15, $response->json('data'));

        // Request second page
        $response = $this->getJson('/api/translations?page=2');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'total' => 20,
                    'per_page' => 15,
                    'current_page' => 2,
                    'last_page' => 2,
                    'from' => 16,
                    'to' => 20,
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_authenticated_user_can_create_translation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', [
                'key' => 'auth.login.title',
                'locale' => 'en',
                'content' => 'Login',
                'tags' => ['web', 'mobile'],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'key',
                'locale',
                'content',
                'tags',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'key' => 'auth.login.title',
                'locale' => 'en',
                'content' => 'Login',
            ]);

        $this->assertDatabaseHas('translations', [
            'key' => 'auth.login.title',
            'locale' => 'en',
            'content' => 'Login',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_translation(): void
    {
        $response = $this->postJson('/api/translations', [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_translation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $translation = Translation::create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Original content',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/translations/{$translation->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $translation->id,
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Updated content',
            ]);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_translation(): void
    {
        $translation = Translation::create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Original content',
        ]);

        $response = $this->putJson("/api/translations/{$translation->id}", [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_delete_translation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $translation = Translation::create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Content to delete',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('translations', [
            'id' => $translation->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_translation(): void
    {
        $translation = Translation::create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Content to delete',
        ]);

        $response = $this->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
        ]);
    }

    public function test_show_returns_single_translation_with_tags(): void
    {
        $translation = Translation::create([
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ]);

        $response = $this->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'key',
                'locale',
                'content',
                'tags',
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $translation->id,
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
            ]);
    }
}
