<?php

namespace Tests\Feature\Properties;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Test for Authentication Enforcement
 * 
 * Feature: translation-management-api
 * Property 13: Authentication Requirement Enforcement
 * 
 * **Validates: Requirements 6.3, 6.5**
 * 
 * Property Statement:
 * For any write endpoint (create, update, delete), requests without a valid API_Token 
 * should be rejected with 401 Unauthorized, and requests with a valid token should be 
 * allowed to proceed.
 */
class AuthenticationEnforcementPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Write endpoints reject requests without authentication
     * 
     * Tests that all write endpoints consistently reject unauthenticated requests
     * across multiple iterations with varied data.
     */
    public function test_property_write_endpoints_reject_unauthenticated_requests(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random translation data for each iteration
            $key = fake()->words(rand(1, 3), true);
            $locale = fake()->randomElement(['en', 'fr', 'es', 'de', 'it', 'pt', 'ja', 'zh']);
            $content = fake()->sentence(rand(3, 15));
            $tags = fake()->randomElement([
                [],
                [fake()->word()],
                [fake()->word(), fake()->word()],
                [fake()->word(), fake()->word(), fake()->word()],
            ]);

            // Test POST /api/translations without authentication
            $response = $this->postJson('/api/translations', [
                'key' => $key,
                'locale' => $locale,
                'content' => $content,
                'tags' => $tags,
            ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: POST /api/translations should return 401 without authentication"
            );

            // Create a translation for update/delete tests
            $translation = Translation::factory()->create([
                'key' => $key,
                'locale' => $locale,
                'content' => $content,
            ]);

            // Test PUT /api/translations/{id} without authentication
            $updatedContent = fake()->sentence(rand(3, 15));
            $response = $this->putJson("/api/translations/{$translation->id}", [
                'key' => $key,
                'locale' => $locale,
                'content' => $updatedContent,
                'tags' => $tags,
            ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: PUT /api/translations/{id} should return 401 without authentication"
            );

            // Test DELETE /api/translations/{id} without authentication
            $response = $this->deleteJson("/api/translations/{$translation->id}");

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: DELETE /api/translations/{id} should return 401 without authentication"
            );

            // Verify translation was not deleted
            $this->assertDatabaseHas('translations', [
                'id' => $translation->id,
                'key' => $key,
            ]);

            // Clean up for next iteration
            $translation->delete();
        }
    }

    /**
     * Property Test: Write endpoints accept requests with valid authentication
     * 
     * Tests that all write endpoints consistently accept authenticated requests
     * across multiple iterations with varied data and different users.
     */
    public function test_property_write_endpoints_accept_authenticated_requests(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a new user for each iteration to test token isolation
            $user = User::factory()->create();
            $token = $user->createToken('test-device-' . $i)->plainTextToken;

            // Generate random translation data
            $key = 'test.key.' . uniqid() . '.' . $i;
            $locale = fake()->randomElement(['en', 'fr', 'es', 'de', 'it', 'pt', 'ja', 'zh']);
            $content = fake()->sentence(rand(3, 15));
            $tags = fake()->randomElement([
                [],
                ['tag-' . $i . '-a'],
                ['tag-' . $i . '-a', 'tag-' . $i . '-b'],
            ]);

            // Test POST /api/translations with authentication
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
                "Iteration {$i}: POST /api/translations should return 201 with valid authentication"
            );

            $translationId = $response->json('id');
            $this->assertNotNull($translationId);

            // Verify translation was created
            $this->assertDatabaseHas('translations', [
                'id' => $translationId,
                'key' => $key,
                'locale' => $locale,
                'content' => $content,
            ]);

            // Test PUT /api/translations/{id} with authentication
            $updatedContent = fake()->sentence(rand(3, 15));
            $updatedTags = fake()->randomElement([
                [],
                ['tag-' . $i . '-1'],
                ['tag-' . $i . '-1', 'tag-' . $i . '-2', 'tag-' . $i . '-3'],
            ]);

            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->putJson("/api/translations/{$translationId}", [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $updatedContent,
                    'tags' => $updatedTags,
                ]);

            $this->assertEquals(
                200,
                $response->status(),
                "Iteration {$i}: PUT /api/translations/{id} should return 200 with valid authentication"
            );

            // Verify translation was updated
            $this->assertDatabaseHas('translations', [
                'id' => $translationId,
                'content' => $updatedContent,
            ]);

            // Test DELETE /api/translations/{id} with authentication
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->deleteJson("/api/translations/{$translationId}");

            $this->assertEquals(
                204,
                $response->status(),
                "Iteration {$i}: DELETE /api/translations/{id} should return 204 with valid authentication"
            );

            // Verify translation was deleted
            $this->assertDatabaseMissing('translations', [
                'id' => $translationId,
            ]);
        }
    }

    /**
     * Property Test: Invalid tokens are consistently rejected
     * 
     * Tests that malformed, expired, or invalid tokens are consistently rejected
     * across all write endpoints.
     */
    public function test_property_invalid_tokens_are_rejected(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate various types of invalid tokens
            $invalidTokens = [
                'invalid-token-' . fake()->uuid(),
                fake()->sha256(),
                fake()->md5(),
                'Bearer-' . fake()->uuid(),
                str_repeat('a', rand(10, 100)),
                fake()->numerify('###-###-###'),
            ];

            $invalidToken = fake()->randomElement($invalidTokens);

            // Generate random translation data with unique key
            $key = fake()->unique()->words(rand(1, 3), true);
            $locale = fake()->randomElement(['en', 'fr', 'es']);
            $content = fake()->sentence();

            // Create a translation for update/delete tests with unique key+locale
            $translation = Translation::factory()->create([
                'key' => $key . '-' . $i,
                'locale' => $locale,
            ]);

            // Test POST with invalid token
            $response = $this->withHeader('Authorization', "Bearer {$invalidToken}")
                ->postJson('/api/translations', [
                    'key' => $key,
                    'locale' => $locale,
                    'content' => $content,
                ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: POST should reject invalid token"
            );

            // Test PUT with invalid token
            $response = $this->withHeader('Authorization', "Bearer {$invalidToken}")
                ->putJson("/api/translations/{$translation->id}", [
                    'content' => fake()->sentence(),
                ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: PUT should reject invalid token"
            );

            // Test DELETE with invalid token
            $response = $this->withHeader('Authorization', "Bearer {$invalidToken}")
                ->deleteJson("/api/translations/{$translation->id}");

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: DELETE should reject invalid token"
            );

            // Verify translation was not affected
            $this->assertDatabaseHas('translations', [
                'id' => $translation->id,
            ]);
        }
    }

    /**
     * Property Test: Malformed authorization headers are rejected
     * 
     * Tests that various malformed authorization header formats are consistently
     * rejected across all write endpoints.
     */
    public function test_property_malformed_authorization_headers_are_rejected(): void
    {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            // Create a valid user and token
            $user = User::factory()->create();
            $validToken = $user->createToken('test')->plainTextToken;

            // Generate various malformed authorization headers
            // Note: Laravel Sanctum is case-insensitive for "Bearer", so we test other malformations
            $malformedHeaders = [
                $validToken, // Missing "Bearer" prefix
                "Token {$validToken}", // Wrong prefix
                "Bearer{$validToken}", // Missing space
                "Bearer  {$validToken}", // Extra space
                "Bearer {$validToken} extra", // Extra content
            ];

            $malformedHeader = fake()->randomElement($malformedHeaders);

            // Create a translation for testing
            $translation = Translation::factory()->create();

            // Test POST with malformed header
            $response = $this->withHeader('Authorization', $malformedHeader)
                ->postJson('/api/translations', [
                    'key' => fake()->words(2, true),
                    'locale' => 'en',
                    'content' => fake()->sentence(),
                ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: POST should reject malformed authorization header: {$malformedHeader}"
            );

            // Test PUT with malformed header
            $response = $this->withHeader('Authorization', $malformedHeader)
                ->putJson("/api/translations/{$translation->id}", [
                    'content' => fake()->sentence(),
                ]);

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: PUT should reject malformed authorization header"
            );

            // Test DELETE with malformed header
            $response = $this->withHeader('Authorization', $malformedHeader)
                ->deleteJson("/api/translations/{$translation->id}");

            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: DELETE should reject malformed authorization header"
            );
        }
    }

    /**
     * Property Test: Read endpoints remain accessible without authentication
     * 
     * Tests that read endpoints consistently allow access without authentication
     * while write endpoints require it, ensuring proper endpoint protection.
     */
    public function test_property_read_endpoints_accessible_without_authentication(): void
    {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            // Create random translations with unique keys
            $translationCount = rand(1, 5);
            $translations = collect();
            for ($j = 0; $j < $translationCount; $j++) {
                $translations->push(Translation::factory()->create([
                    'key' => fake()->unique()->words(rand(1, 3), true) . '-' . $i . '-' . $j,
                ]));
            }
            $translation = $translations->random();

            // Test GET /api/translations (index)
            $response = $this->getJson('/api/translations');
            $this->assertEquals(
                200,
                $response->status(),
                "Iteration {$i}: GET /api/translations should be accessible without authentication"
            );

            // Test GET /api/translations/{id} (show)
            $response = $this->getJson("/api/translations/{$translation->id}");
            $this->assertEquals(
                200,
                $response->status(),
                "Iteration {$i}: GET /api/translations/{id} should be accessible without authentication"
            );

            // Test GET /api/translations/search
            $searchParams = http_build_query([
                'key' => fake()->optional()->word(),
                'locale' => fake()->optional()->randomElement(['en', 'fr', 'es']),
            ]);
            $response = $this->getJson("/api/translations/search?{$searchParams}");
            $this->assertEquals(
                200,
                $response->status(),
                "Iteration {$i}: GET /api/translations/search should be accessible without authentication"
            );

            // But write operations should still require authentication
            $response = $this->postJson('/api/translations', [
                'key' => fake()->words(2, true),
                'locale' => 'en',
                'content' => fake()->sentence(),
            ]);
            $this->assertEquals(
                401,
                $response->status(),
                "Iteration {$i}: POST should still require authentication"
            );
        }
    }
}
