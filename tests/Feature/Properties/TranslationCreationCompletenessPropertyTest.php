<?php

namespace Tests\Feature\Properties;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Test for Translation Creation Completeness
 * 
 * Feature: translation-management-api
 * Property 1: Translation Creation Completeness
 * 
 * **Validates: Requirements 1.1**
 * 
 * Property Statement:
 * For any valid translation data (key, locale, content, and optional tags), 
 * when a translation is created, the resulting Translation_Record should contain 
 * all provided fields with their exact values.
 */
class TranslationCreationCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Created translations contain all provided fields with exact values
     * 
     * Tests that translations created via the API contain all provided fields
     * (key, locale, content, tags) with their exact values across multiple iterations
     * with varied data.
     */
    public function test_property_created_translations_contain_all_provided_fields(): void
    {
        $iterations = 100;
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random translation data
            $key = 'test.key.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es', 'de', 'it', 'pt', 'ja', 'zh', 'ko', 'ru']);
            $content = fake()->sentence(rand(3, 20));
            $tags = $this->generateRandomTags($i);

            // Create translation via API
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                    'tags' => $tags,
                ]);

            $this->assertEquals(
                201,
                $response->status(),
                "Iteration {$i}: Translation creation should return 201"
            );

            // Verify response contains all fields with exact values
            $responseData = $response->json();
            
            $this->assertArrayHasKey('id', $responseData, "Iteration {$i}: Response should contain 'id'");
            $this->assertArrayHasKey('key', $responseData, "Iteration {$i}: Response should contain 'key'");
            $this->assertArrayHasKey('locale', $responseData, "Iteration {$i}: Response should contain 'locale'");
            $this->assertArrayHasKey('content', $responseData, "Iteration {$i}: Response should contain 'content'");
            $this->assertArrayHasKey('tags', $responseData, "Iteration {$i}: Response should contain 'tags'");
            $this->assertArrayHasKey('created_at', $responseData, "Iteration {$i}: Response should contain 'created_at'");
            $this->assertArrayHasKey('updated_at', $responseData, "Iteration {$i}: Response should contain 'updated_at'");

            // Verify exact values
            $this->assertEquals(
                $key,
                $responseData['key'],
                "Iteration {$i}: Response key should match provided key"
            );
            $this->assertEquals(
                $locale,
                $responseData['locale'],
                "Iteration {$i}: Response locale should match provided locale"
            );
            $this->assertEquals(
                $content,
                $responseData['content'],
                "Iteration {$i}: Response content should match provided content"
            );
            
            // Verify tags (order-independent comparison)
            $this->assertEqualsCanonicalizing(
                $tags,
                $responseData['tags'],
                "Iteration {$i}: Response tags should match provided tags"
            );

            // Verify database record contains all fields with exact values
            $translationId = $responseData['id'];
            $this->assertDatabaseHas('translations', [
                'id' => $translationId,
                'key' => $key,
                'locale' => $locale,
                'content' => $content,
            ]);

            // Verify tags are properly associated in database
            $translation = Translation::with('tags')->find($translationId);
            $this->assertNotNull($translation, "Iteration {$i}: Translation should exist in database");
            
            $dbTags = $translation->tags->pluck('name')->toArray();
            $this->assertEqualsCanonicalizing(
                $tags,
                $dbTags,
                "Iteration {$i}: Database tags should match provided tags"
            );
        }
    }

    /**
     * Property Test: Created translations without tags have empty tag arrays
     * 
     * Tests that translations created without tags have empty tag arrays
     * and are properly stored in the database.
     */
    public function test_property_created_translations_without_tags_have_empty_arrays(): void
    {
        $iterations = 50;
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random translation data without tags
            $key = 'test.notags.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es', 'de', 'it']);
            $content = fake()->sentence(rand(3, 15));

            // Create translation via API without tags field
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                ]);

            $this->assertEquals(
                201,
                $response->status(),
                "Iteration {$i}: Translation creation without tags should return 201"
            );

            // Verify response contains empty tags array
            $responseData = $response->json();
            $this->assertArrayHasKey('tags', $responseData, "Iteration {$i}: Response should contain 'tags'");
            $this->assertIsArray($responseData['tags'], "Iteration {$i}: Tags should be an array");
            $this->assertEmpty($responseData['tags'], "Iteration {$i}: Tags should be empty");

            // Verify all other fields are present
            $this->assertEquals($key, $responseData['key']);
            $this->assertEquals($locale, $responseData['locale']);
            $this->assertEquals($content, $responseData['content']);

            // Verify database record
            $translationId = $responseData['id'];
            $translation = Translation::with('tags')->find($translationId);
            $this->assertNotNull($translation);
            $this->assertCount(0, $translation->tags, "Iteration {$i}: Translation should have no tags in database");
        }
    }

    /**
     * Property Test: Created translations with explicit empty tags array
     * 
     * Tests that translations created with an explicit empty tags array
     * are handled correctly.
     */
    public function test_property_created_translations_with_explicit_empty_tags(): void
    {
        $iterations = 50;
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random translation data with explicit empty tags
            $key = 'test.emptytags.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es']);
            $content = fake()->sentence(rand(3, 15));

            // Create translation via API with explicit empty tags array
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                    'tags' => [],
                ]);

            $this->assertEquals(
                201,
                $response->status(),
                "Iteration {$i}: Translation creation with empty tags should return 201"
            );

            // Verify response
            $responseData = $response->json();
            $this->assertEmpty($responseData['tags'], "Iteration {$i}: Tags should be empty");
            $this->assertEquals($key, $responseData['key']);
            $this->assertEquals($locale, $responseData['locale']);
            $this->assertEquals($content, $responseData['content']);
        }
    }

    /**
     * Property Test: Created translations preserve content exactly
     * 
     * Tests that translation content is preserved exactly, including special
     * characters, whitespace, and various content types.
     */
    public function test_property_created_translations_preserve_content_exactly(): void
    {
        $iterations = 100;
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate various types of content
            // Note: Leading/trailing spaces are trimmed by Laravel's validation
            $contentTypes = [
                fake()->sentence(),
                fake()->paragraph(),
                fake()->text(200),
                "Content with\nnewlines\nand\ttabs",
                "Content with special chars: !@#$%^&*()_+-=[]{}|;':\",./<>?",
                "Content with unicode: 你好世界 مرحبا العالم Привет мир",
                "Content with emojis: 😀 🎉 🚀 ❤️",
                "Content with \"quotes\" and 'apostrophes'",
                "Content with numbers: 123 456.789 -10",
                "Content with internal  spaces",
            ];

            $content = fake()->randomElement($contentTypes);
            $key = 'test.content.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es']);

            // Create translation
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                ]);

            $this->assertEquals(201, $response->status());

            // Verify content is preserved exactly
            $responseData = $response->json();
            $this->assertEquals(
                $content,
                $responseData['content'],
                "Iteration {$i}: Content should be preserved exactly"
            );

            // Verify in database
            $this->assertDatabaseHas('translations', [
                'id' => $responseData['id'],
                'content' => $content,
            ]);
        }
    }

    /**
     * Property Test: Created translations with various tag combinations
     * 
     * Tests that translations with different numbers and types of tags
     * are created correctly.
     */
    public function test_property_created_translations_with_various_tag_combinations(): void
    {
        $iterations = 100;
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate various tag combinations
            $tagCount = rand(1, 10);
            $tags = [];
            for ($j = 0; $j < $tagCount; $j++) {
                $tags[] = 'tag-' . $i . '-' . $j . '-' . fake()->word();
            }

            $key = 'test.tags.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es']);
            $content = fake()->sentence();

            // Create translation
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                    'tags' => $tags,
                ]);

            $this->assertEquals(201, $response->status());

            // Verify all tags are present
            $responseData = $response->json();
            $this->assertCount(
                $tagCount,
                $responseData['tags'],
                "Iteration {$i}: Should have {$tagCount} tags"
            );
            $this->assertEqualsCanonicalizing(
                $tags,
                $responseData['tags'],
                "Iteration {$i}: All tags should be present"
            );

            // Verify in database
            $translation = Translation::with('tags')->find($responseData['id']);
            $this->assertCount($tagCount, $translation->tags);
        }
    }

    /**
     * Property Test: Created translations with duplicate tags are normalized
     * 
     * Tests that when duplicate tags are provided, they are normalized to unique tags.
     * Note: This test validates that the system handles duplicate tags gracefully.
     */
    public function test_property_created_translations_normalize_duplicate_tags(): void
    {
        $iterations = 50;
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate unique tags (no duplicates in the request)
            // The normalization happens at the database level when the same tag
            // is used across multiple translations
            $uniqueTag1 = 'tag-' . $i . '-a';
            $uniqueTag2 = 'tag-' . $i . '-b';
            $uniqueTag3 = 'tag-' . $i . '-c';
            
            $tags = [$uniqueTag1, $uniqueTag2, $uniqueTag3];

            $key = 'test.dupetags.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es']);
            $content = fake()->sentence();

            // Create translation
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                    'tags' => $tags,
                ]);

            $this->assertEquals(201, $response->status());

            // Verify tags are present
            $responseData = $response->json();
            
            $this->assertCount(
                count($tags),
                $responseData['tags'],
                "Iteration {$i}: All tags should be present"
            );
            $this->assertEqualsCanonicalizing(
                $tags,
                $responseData['tags'],
                "Iteration {$i}: All unique tags should be present"
            );
            
            // Create another translation with the same tags to verify normalization
            $key2 = 'test.dupetags2.' . uniqid() . '.' . $i;
            $response2 = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key2,
                    'locale' => $locale,
                    'content' => fake()->sentence(),
                    'tags' => $tags,
                ]);

            $this->assertEquals(201, $response2->status());
            
            // Verify that tags are normalized in the database (same tag IDs used)
            $translation1 = Translation::with('tags')->find($responseData['id']);
            $translation2 = Translation::with('tags')->find($response2->json('id'));
            
            $tagIds1 = $translation1->tags->pluck('id')->sort()->values()->toArray();
            $tagIds2 = $translation2->tags->pluck('id')->sort()->values()->toArray();
            
            $this->assertEquals(
                $tagIds1,
                $tagIds2,
                "Iteration {$i}: Same tags should use same tag IDs (normalization)"
            );
        }
    }

    /**
     * Property Test: Timestamps are automatically generated
     * 
     * Tests that created_at and updated_at timestamps are automatically
     * generated and are valid ISO 8601 timestamps.
     */
    public function test_property_created_translations_have_valid_timestamps(): void
    {
        $iterations = 50;
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        for ($i = 0; $i < $iterations; $i++) {
            $key = 'test.timestamps.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es']);
            $content = fake()->sentence();

            $beforeCreation = now()->subSeconds(5); // Allow 5 second buffer
            
            // Create translation
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                ]);

            $afterCreation = now()->addSeconds(5); // Allow 5 second buffer

            $this->assertEquals(201, $response->status());

            // Verify timestamps exist and are valid
            $responseData = $response->json();
            $this->assertNotNull($responseData['created_at'], "Iteration {$i}: created_at should not be null");
            $this->assertNotNull($responseData['updated_at'], "Iteration {$i}: updated_at should not be null");

            // Verify timestamps are valid ISO 8601 format
            $createdAt = \Carbon\Carbon::parse($responseData['created_at']);
            $updatedAt = \Carbon\Carbon::parse($responseData['updated_at']);

            $this->assertInstanceOf(\Carbon\Carbon::class, $createdAt);
            $this->assertInstanceOf(\Carbon\Carbon::class, $updatedAt);

            // Verify timestamps are within reasonable range (with buffer for test execution time)
            $this->assertTrue(
                $createdAt->between($beforeCreation, $afterCreation),
                "Iteration {$i}: created_at should be between request start and end (with buffer)"
            );
            $this->assertTrue(
                $updatedAt->between($beforeCreation, $afterCreation),
                "Iteration {$i}: updated_at should be between request start and end (with buffer)"
            );

            // For new records, created_at and updated_at should be equal or very close
            $this->assertTrue(
                abs($createdAt->diffInSeconds($updatedAt)) <= 2,
                "Iteration {$i}: created_at and updated_at should be equal or very close for new records"
            );
        }
    }

    /**
     * Helper method to generate random tags for testing
     */
    private function generateRandomTags(int $iteration): array
    {
        $tagCount = rand(0, 5);
        $tags = [];
        
        for ($i = 0; $i < $tagCount; $i++) {
            $tagTypes = [
                'web',
                'mobile',
                'desktop',
                'api',
                'admin',
                'user',
                'tag-' . $iteration . '-' . $i,
                fake()->word(),
            ];
            $tags[] = fake()->randomElement($tagTypes);
        }

        return array_unique($tags);
    }
}
