<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_endpoints_are_accessible_without_authentication(): void
    {
        Translation::factory()->create();

        // Test index
        $response = $this->getJson('/api/translations');
        $response->assertStatus(200);

        // Test show
        $translation = Translation::first();
        $response = $this->getJson("/api/translations/{$translation->id}");
        $response->assertStatus(200);

        // Test search
        $response = $this->getJson('/api/translations/search');
        $response->assertStatus(200);
    }

    public function test_write_endpoints_require_authentication(): void
    {
        $translation = Translation::factory()->create();

        // Test store without auth
        $response = $this->postJson('/api/translations', [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ]);
        $response->assertStatus(401);

        // Test update without auth
        $response = $this->putJson("/api/translations/{$translation->id}", [
            'content' => 'Updated content',
        ]);
        $response->assertStatus(401);

        // Test delete without auth
        $response = $this->deleteJson("/api/translations/{$translation->id}");
        $response->assertStatus(401);
    }

    public function test_write_endpoints_work_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Test store with auth
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
            ]);
        $response->assertStatus(201);

        $translation = Translation::where('key', 'test.key')->first();

        // Test update with auth
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/translations/{$translation->id}", [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Updated content',
            ]);
        $response->assertStatus(200);

        // Test delete with auth
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/translations/{$translation->id}");
        $response->assertStatus(204);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-12345')
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'locale' => 'en',
                'content' => 'Test content',
            ]);
        $response->assertStatus(401);
    }
}
