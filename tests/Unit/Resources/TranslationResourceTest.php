<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\TranslationResource;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_formats_translation_correctly(): void
    {
        // Create a translation with tags
        $translation = Translation::create([
            'key' => 'auth.login.title',
            'locale' => 'en',
            'content' => 'Login',
        ]);

        $tag1 = Tag::create(['name' => 'web']);
        $tag2 = Tag::create(['name' => 'mobile']);
        $translation->tags()->attach([$tag1->id, $tag2->id]);

        // Reload with tags
        $translation = $translation->fresh(['tags']);

        // Create resource
        $resource = new TranslationResource($translation);
        $array = $resource->toArray(request());

        // Assert structure
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('key', $array);
        $this->assertArrayHasKey('locale', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        // Assert values
        $this->assertEquals($translation->id, $array['id']);
        $this->assertEquals('auth.login.title', $array['key']);
        $this->assertEquals('en', $array['locale']);
        $this->assertEquals('Login', $array['content']);
        $this->assertIsArray($array['tags']->toArray());
        $this->assertCount(2, $array['tags']);
        $this->assertContains('web', $array['tags']->toArray());
        $this->assertContains('mobile', $array['tags']->toArray());

        // Assert timestamp format (ISO 8601)
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $array['created_at']
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $array['updated_at']
        );
    }

    public function test_resource_handles_translation_without_tags(): void
    {
        // Create a translation without tags
        $translation = Translation::create([
            'key' => 'test.key',
            'locale' => 'fr',
            'content' => 'Test content',
        ]);

        // Create resource
        $resource = new TranslationResource($translation);
        $array = $resource->toArray(request());

        // Assert tags is an empty collection
        $this->assertArrayHasKey('tags', $array);
        $this->assertCount(0, $array['tags']);
    }
}
