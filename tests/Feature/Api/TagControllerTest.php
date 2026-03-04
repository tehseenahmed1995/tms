<?php

namespace Tests\Feature\Api;

use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_unique_tags(): void
    {
        // Create translations with tags
        $translation1 = Translation::create([
            'key' => 'test.key1',
            'locale' => 'en',
            'content' => 'Content 1',
        ]);

        $translation2 = Translation::create([
            'key' => 'test.key2',
            'locale' => 'en',
            'content' => 'Content 2',
        ]);

        // Create tags
        $tag1 = Tag::create(['name' => 'web']);
        $tag2 = Tag::create(['name' => 'mobile']);
        $tag3 = Tag::create(['name' => 'desktop']);

        // Associate tags with translations
        $translation1->tags()->attach([$tag1->id, $tag2->id]);
        $translation2->tags()->attach([$tag2->id, $tag3->id]);

        // Make request
        $response = $this->getJson('/api/tags');

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ])
            ->assertJson([
                'data' => ['desktop', 'mobile', 'web'],
            ]);

        // Verify we have exactly 3 unique tags
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_returns_empty_array_when_no_tags_exist(): void
    {
        // Make request without any tags in database
        $response = $this->getJson('/api/tags');

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    public function test_index_returns_tags_in_alphabetical_order(): void
    {
        // Create tags in non-alphabetical order
        Tag::create(['name' => 'zebra']);
        Tag::create(['name' => 'apple']);
        Tag::create(['name' => 'mobile']);

        // Make request
        $response = $this->getJson('/api/tags');

        // Assert response is in alphabetical order
        $response->assertStatus(200)
            ->assertJson([
                'data' => ['apple', 'mobile', 'zebra'],
            ]);
    }
}
