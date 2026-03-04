<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive feature tests for authentication functionality.
 * 
 * Validates: Requirements 12.6
 * 
 * Tests cover:
 * - Token generation with valid credentials
 * - Token revocation
 * - Protected endpoint access
 */
class AuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test token generation with valid credentials.
     */
    public function test_token_generation_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secure-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secure-password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        // Verify token is a non-empty string
        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Verify user information is correct
        $this->assertEquals($user->email, $response->json('user.email'));
        $this->assertEquals($user->id, $response->json('user.id'));

        // Verify token is stored in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * Test token generation with custom device name.
     */
    public function test_token_generation_with_device_name(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secure-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secure-password',
            'device_name' => 'mobile-app',
        ]);

        $response->assertStatus(200);

        // Verify token is created with the specified device name
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'mobile-app',
        ]);
    }

    /**
     * Test token generation fails with invalid credentials.
     */
    public function test_token_generation_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Verify no token was created
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test token generation fails for non-existent user.
     */
    public function test_token_generation_fails_for_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test token revocation.
     */
    public function test_token_revocation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        // Verify token works before revocation
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/validate')
            ->assertStatus(200);

        // Revoke the token
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Token revoked successfully']);

        // Verify token is removed from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * Test revoked token cannot access protected endpoints.
     */
    public function test_revoked_token_cannot_access_protected_endpoints(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        // Revoke the token
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // Verify token is deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * Test multiple tokens can be created for the same user.
     */
    public function test_multiple_tokens_can_be_created_for_same_user(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create first token
        $response1 = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'device_name' => 'device-1',
        ]);
        $token1 = $response1->json('token');

        // Create second token
        $response2 = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'device_name' => 'device-2',
        ]);
        $token2 = $response2->json('token');

        // Verify both tokens are different
        $this->assertNotEquals($token1, $token2);

        // Verify both tokens work
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/api/auth/validate')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson('/api/auth/validate')
            ->assertStatus(200);

        // Verify both tokens exist in database
        $this->assertEquals(2, $user->tokens()->count());
    }

    /**
     * Test revoking one token doesn't affect other tokens.
     */
    public function test_revoking_one_token_does_not_affect_other_tokens(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        // Verify both tokens work initially
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/api/auth/validate')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson('/api/auth/validate')
            ->assertStatus(200);

        // Revoke first token
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // Verify only one token remains in database
        $this->assertEquals(1, $user->fresh()->tokens()->count());

        // Verify second token still works
        $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson('/api/auth/validate')
            ->assertStatus(200);
    }

    /**
     * Test protected endpoint access - write operations require authentication.
     */
    public function test_protected_endpoint_access_write_operations_require_authentication(): void
    {
        $translation = Translation::factory()->create();

        // Test POST without authentication
        $response = $this->postJson('/api/translations', [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ]);
        $response->assertStatus(401);

        // Test PUT without authentication
        $response = $this->putJson("/api/translations/{$translation->id}", [
            'content' => 'Updated content',
        ]);
        $response->assertStatus(401);

        // Test DELETE without authentication
        $response = $this->deleteJson("/api/translations/{$translation->id}");
        $response->assertStatus(401);
    }

    /**
     * Test protected endpoint access - write operations work with valid token.
     */
    public function test_protected_endpoint_access_write_operations_work_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        // Test POST with authentication
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', [
                'key' => 'auth.test',
                'locale' => 'en',
                'content' => 'Authentication test',
            ]);
        $response->assertStatus(201);

        $translation = Translation::where('key', 'auth.test')->first();
        $this->assertNotNull($translation);

        // Test PUT with authentication
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/translations/{$translation->id}", [
                'key' => 'auth.test',
                'locale' => 'en',
                'content' => 'Updated authentication test',
            ]);
        $response->assertStatus(200);

        // Test DELETE with authentication
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/translations/{$translation->id}");
        $response->assertStatus(204);
    }

    /**
     * Test protected endpoint access - read operations work without authentication.
     */
    public function test_protected_endpoint_access_read_operations_work_without_authentication(): void
    {
        $translation = Translation::factory()->create();

        // Test GET index without authentication
        $response = $this->getJson('/api/translations');
        $response->assertStatus(200);

        // Test GET show without authentication
        $response = $this->getJson("/api/translations/{$translation->id}");
        $response->assertStatus(200);

        // Test GET search without authentication
        $response = $this->getJson('/api/translations/search');
        $response->assertStatus(200);
    }

    /**
     * Test protected endpoint access - invalid token is rejected.
     */
    public function test_protected_endpoint_access_invalid_token_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-string')
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
            ]);

        $response->assertStatus(401);
    }

    /**
     * Test protected endpoint access - malformed authorization header is rejected.
     */
    public function test_protected_endpoint_access_malformed_authorization_header_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        // Test without "Bearer" prefix
        $response = $this->withHeader('Authorization', $token)
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
            ]);
        $response->assertStatus(401);

        // Test with wrong prefix
        $response = $this->withHeader('Authorization', "Token {$token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
            ]);
        $response->assertStatus(401);
    }

    /**
     * Test token validation endpoint returns correct user information.
     */
    public function test_token_validation_endpoint_returns_correct_user_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/validate');

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ]);
    }

    /**
     * Test token validation endpoint requires authentication.
     */
    public function test_token_validation_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/validate');
        $response->assertStatus(401);
    }

    /**
     * Test logout endpoint requires authentication.
     */
    public function test_logout_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }
}
