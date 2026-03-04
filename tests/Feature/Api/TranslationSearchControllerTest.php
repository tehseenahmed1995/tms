<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_by_key_returns_matching_translations(): void
    {
        Translation::factory()->create(['key' => 'auth.login.title', 'locale' => 'en', 'content' => 'Login']);
        Translation::factory()->create(['key' => 'auth.logout.title', 'locale' => 'en', 'content' => 'Logout']);
        Translation::factory()->create(['key' => 'home.welcome', 'locale' => 'en', 'content' => 'Welcome']);

        $response = $this->getJson('/api/translations/search?key=auth');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['key' => 'auth.login.title'])
            ->assertJsonFragment(['key' => 'auth.logout.title']);
    }

    public function test_search_by_locale_returns_matching_translations(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'English']);
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'fr', 'content' => 'French']);
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'es', 'content' => 'Spanish']);

        $response = $this->getJson('/api/translations/search?locale=fr');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['locale' => 'fr', 'content' => 'French']);
    }

    public function test_search_by_content_returns_matching_translations(): void
    {
        Translation::factory()->create(['key' => 'key1', 'locale' => 'en', 'content' => 'Hello World']);
        Translation::factory()->create(['key' => 'key2', 'locale' => 'en', 'content' => 'Goodbye World']);
        Translation::factory()->create(['key' => 'key3', 'locale' => 'en', 'content' => 'Test Message']);

        $response = $this->getJson('/api/translations/search?content=World');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['content' => 'Hello World'])
            ->assertJsonFragment(['content' => 'Goodbye World']);
    }

    public function test_search_by_single_tag_returns_matching_translations(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);

        $translation1 = Translation::factory()->create(['key' => 'key1', 'locale' => 'en']);
        $translation1->tags()->attach($webTag->id);

        $translation2 = Translation::factory()->create(['key' => 'key2', 'locale' => 'en']);
        $translation2->tags()->attach($mobileTag->id);

        $translation3 = Translation::factory()->create(['key' => 'key3', 'locale' => 'en']);
        $translation3->tags()->attach([$webTag->id, $mobileTag->id]);

        $response = $this->getJson('/api/translations/search?tags[]=web');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_search_by_multiple_tags_uses_and_logic(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);
        $desktopTag = Tag::factory()->create(['name' => 'desktop']);

        $translation1 = Translation::factory()->create(['key' => 'key1', 'locale' => 'en']);
        $translation1->tags()->attach($webTag->id);

        $translation2 = Translation::factory()->create(['key' => 'key2', 'locale' => 'en']);
        $translation2->tags()->attach([$webTag->id, $mobileTag->id]);

        $translation3 = Translation::factory()->create(['key' => 'key3', 'locale' => 'en']);
        $translation3->tags()->attach([$webTag->id, $mobileTag->id, $desktopTag->id]);

        $response = $this->getJson('/api/translations/search?tags[]=web&tags[]=mobile');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['key' => 'key2'])
            ->assertJsonFragment(['key' => 'key3']);
    }

    public function test_search_with_combined_criteria_uses_and_logic(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);

        $translation1 = Translation::factory()->create([
            'key' => 'auth.login',
            'locale' => 'en',
            'content' => 'Login Page'
        ]);
        $translation1->tags()->attach($webTag->id);

        $translation2 = Translation::factory()->create([
            'key' => 'auth.logout',
            'locale' => 'en',
            'content' => 'Logout Page'
        ]);
        $translation2->tags()->attach($webTag->id);

        $translation3 = Translation::factory()->create([
            'key' => 'auth.login',
            'locale' => 'fr',
            'content' => 'Page de connexion'
        ]);
        $translation3->tags()->attach($webTag->id);

        $response = $this->getJson('/api/translations/search?key=auth&locale=en&content=Login&tags[]=web');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['key' => 'auth.login', 'locale' => 'en']);
    }

    public function test_search_returns_paginated_results(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Translation::factory()->create([
                'key' => "test.key.{$i}",
                'locale' => 'en'
            ]);
        }

        $response = $this->getJson('/api/translations/search?locale=en&per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ]);
    }

    public function test_search_with_custom_per_page(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Translation::factory()->create([
                'key' => "test.key.{$i}",
                'locale' => 'en'
            ]);
        }

        $response = $this->getJson('/api/translations/search?locale=en&per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_search_returns_empty_array_when_no_matches(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en']);

        $response = $this->getJson('/api/translations/search?key=nonexistent');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_search_includes_tags_in_response(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);

        $translation = Translation::factory()->create(['key' => 'test.key', 'locale' => 'en']);
        $translation->tags()->attach([$webTag->id, $mobileTag->id]);

        $response = $this->getJson('/api/translations/search?key=test');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'tags' => ['web', 'mobile']
            ]);
    }

    public function test_search_validates_locale_format(): void
    {
        $response = $this->getJson('/api/translations/search?locale=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_search_validates_per_page_range(): void
    {
        $response = $this->getJson('/api/translations/search?per_page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        $response = $this->getJson('/api/translations/search?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_search_is_case_insensitive_for_key(): void
    {
        Translation::factory()->create(['key' => 'Auth.Login.Title', 'locale' => 'en']);

        $response = $this->getJson('/api/translations/search?key=auth');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_search_is_case_insensitive_for_content(): void
    {
        Translation::factory()->create(['key' => 'test', 'locale' => 'en', 'content' => 'Hello World']);

        $response = $this->getJson('/api/translations/search?content=hello');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_search_without_parameters_returns_all_translations(): void
    {
        Translation::factory()->count(5)->create();

        $response = $this->getJson('/api/translations/search');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }
}
