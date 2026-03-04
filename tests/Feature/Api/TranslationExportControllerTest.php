<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class TranslationExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_returns_translations_for_locale(): void
    {
        Translation::factory()->create(['key' => 'auth.login', 'locale' => 'en', 'content' => 'Login']);
        Translation::factory()->create(['key' => 'auth.logout', 'locale' => 'en', 'content' => 'Logout']);
        Translation::factory()->create(['key' => 'home.welcome', 'locale' => 'fr', 'content' => 'Bienvenue']);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'auth' => ['login', 'logout']
            ])
            ->assertJsonFragment(['login' => 'Login'])
            ->assertJsonFragment(['logout' => 'Logout']);
    }

    public function test_export_returns_nested_structure_by_default(): void
    {
        Translation::factory()->create(['key' => 'auth.login.title', 'locale' => 'en', 'content' => 'Login Page']);
        Translation::factory()->create(['key' => 'auth.login.button', 'locale' => 'en', 'content' => 'Sign In']);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'auth' => [
                    'login' => ['title', 'button']
                ]
            ]);
    }

    public function test_export_returns_flat_structure_when_nested_is_false(): void
    {
        Translation::factory()->create(['key' => 'auth.login', 'locale' => 'en', 'content' => 'Login']);
        Translation::factory()->create(['key' => 'auth.logout', 'locale' => 'en', 'content' => 'Logout']);

        $response = $this->getJson('/api/translations/export?locale=en&nested=0');

        $response->assertStatus(200)
            ->assertJson([
                'auth.login' => 'Login',
                'auth.logout' => 'Logout'
            ]);
    }

    public function test_export_filters_by_tags(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);

        $translation1 = Translation::factory()->create(['key' => 'web.title', 'locale' => 'en', 'content' => 'Web Title']);
        $translation1->tags()->attach($webTag->id);

        $translation2 = Translation::factory()->create(['key' => 'mobile.title', 'locale' => 'en', 'content' => 'Mobile Title']);
        $translation2->tags()->attach($mobileTag->id);

        $translation3 = Translation::factory()->create(['key' => 'common.title', 'locale' => 'en', 'content' => 'Common Title']);
        $translation3->tags()->attach([$webTag->id, $mobileTag->id]);

        $response = $this->getJson('/api/translations/export?locale=en&tags[]=web');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Web Title'])
            ->assertJsonFragment(['title' => 'Common Title'])
            ->assertJsonMissing(['title' => 'Mobile Title']);
    }

    public function test_export_includes_etag_header(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertHeader('ETag');
        
        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);
    }

    public function test_export_includes_cache_control_header(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control');
        
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);
    }

    public function test_export_includes_last_modified_header(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertHeader('Last-Modified');
        
        $lastModified = $response->headers->get('Last-Modified');
        $this->assertNotEmpty($lastModified);
    }

    public function test_export_returns_304_when_etag_matches(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        // First request to get ETag
        $response1 = $this->getJson('/api/translations/export?locale=en');
        $etag = $response1->headers->get('ETag');

        // Second request with If-None-Match header
        $response2 = $this->getJson('/api/translations/export?locale=en', [
            'If-None-Match' => $etag
        ]);

        $response2->assertStatus(304);
    }

    public function test_export_returns_200_when_etag_does_not_match(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response = $this->getJson('/api/translations/export?locale=en', [
            'If-None-Match' => 'invalid-etag'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['test']);
    }

    public function test_export_etag_changes_when_content_changes(): void
    {
        $translation = Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Original']);

        // Get initial ETag
        $response1 = $this->getJson('/api/translations/export?locale=en');
        $etag1 = $response1->headers->get('ETag');

        // Clear cache to force regeneration
        Cache::flush();

        // Update translation
        $translation->update(['content' => 'Updated']);

        // Get new ETag
        $response2 = $this->getJson('/api/translations/export?locale=en');
        $etag2 = $response2->headers->get('ETag');

        $this->assertNotEquals($etag1, $etag2);
    }

    public function test_export_validates_locale_is_required(): void
    {
        $response = $this->getJson('/api/translations/export');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_export_validates_locale_format(): void
    {
        $response = $this->getJson('/api/translations/export?locale=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_export_accepts_comma_separated_tags(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response = $this->getJson('/api/translations/export?locale=en&tags=web,mobile');

        $response->assertStatus(200);
    }

    public function test_export_accepts_string_boolean_for_nested(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response = $this->getJson('/api/translations/export?locale=en&nested=true');
        $response->assertStatus(200);

        $response = $this->getJson('/api/translations/export?locale=en&nested=false');
        $response->assertStatus(200);

        $response = $this->getJson('/api/translations/export?locale=en&nested=1');
        $response->assertStatus(200);

        $response = $this->getJson('/api/translations/export?locale=en&nested=0');
        $response->assertStatus(200);
    }

    public function test_export_validates_invalid_nested_value(): void
    {
        $response = $this->getJson('/api/translations/export?locale=en&nested=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nested']);
    }

    public function test_export_returns_empty_object_for_locale_with_no_translations(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response = $this->getJson('/api/translations/export?locale=fr');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_export_uses_cache_for_repeated_requests(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        // First request - cache miss
        $response1 = $this->getJson('/api/translations/export?locale=en');
        $response1->assertStatus(200);

        // Delete translation from database
        Translation::where('locale', 'en')->delete();

        // Second request - should still return cached data
        $response2 = $this->getJson('/api/translations/export?locale=en');
        $response2->assertStatus(200)
            ->assertJsonFragment(['key' => 'Test']);
    }

    public function test_export_handles_multiple_tags_filter(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);

        $translation1 = Translation::factory()->create(['key' => 'web.only', 'locale' => 'en', 'content' => 'Web Only']);
        $translation1->tags()->attach($webTag->id);

        $translation2 = Translation::factory()->create(['key' => 'mobile.only', 'locale' => 'en', 'content' => 'Mobile Only']);
        $translation2->tags()->attach($mobileTag->id);

        $translation3 = Translation::factory()->create(['key' => 'both', 'locale' => 'en', 'content' => 'Both']);
        $translation3->tags()->attach([$webTag->id, $mobileTag->id]);

        $response = $this->getJson('/api/translations/export?locale=en&tags[]=web&tags[]=mobile');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(3, array_keys($data));
    }

    public function test_export_handles_deeply_nested_keys(): void
    {
        Translation::factory()->create([
            'key' => 'level1.level2.level3.level4.key',
            'locale' => 'en',
            'content' => 'Deep Value'
        ]);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => ['key']
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('level1.level2.level3.level4.key', 'Deep Value');
    }

    public function test_export_handles_keys_without_dots(): void
    {
        Translation::factory()->create(['key' => 'simplekey', 'locale' => 'en', 'content' => 'Simple Value']);

        $response = $this->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertJson(['simplekey' => 'Simple Value']);
    }

    public function test_export_same_etag_for_same_content(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'Test']);

        $response1 = $this->getJson('/api/translations/export?locale=en');
        $etag1 = $response1->headers->get('ETag');

        // Clear cache and request again
        Cache::flush();
        
        $response2 = $this->getJson('/api/translations/export?locale=en');
        $etag2 = $response2->headers->get('ETag');

        $this->assertEquals($etag1, $etag2);
    }

    public function test_export_different_etag_for_different_locales(): void
    {
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'en', 'content' => 'English']);
        Translation::factory()->create(['key' => 'test.key', 'locale' => 'fr', 'content' => 'French']);

        $response1 = $this->getJson('/api/translations/export?locale=en');
        $etag1 = $response1->headers->get('ETag');

        $response2 = $this->getJson('/api/translations/export?locale=fr');
        $etag2 = $response2->headers->get('ETag');

        $this->assertNotEquals($etag1, $etag2);
    }

    public function test_export_different_etag_for_different_tag_filters(): void
    {
        $webTag = Tag::factory()->create(['name' => 'web']);
        $mobileTag = Tag::factory()->create(['name' => 'mobile']);

        $translation1 = Translation::factory()->create(['key' => 'web.key', 'locale' => 'en', 'content' => 'Web']);
        $translation1->tags()->attach($webTag->id);

        $translation2 = Translation::factory()->create(['key' => 'mobile.key', 'locale' => 'en', 'content' => 'Mobile']);
        $translation2->tags()->attach($mobileTag->id);

        $response1 = $this->getJson('/api/translations/export?locale=en&tags[]=web');
        $etag1 = $response1->headers->get('ETag');

        $response2 = $this->getJson('/api/translations/export?locale=en&tags[]=mobile');
        $etag2 = $response2->headers->get('ETag');

        $this->assertNotEquals($etag1, $etag2);
    }
}
