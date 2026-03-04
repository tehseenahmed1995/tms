<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\TranslationCollection;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_formats_paginated_translations_correctly(): void
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

        // Get paginated translations
        $paginated = Translation::with('tags')->paginate(2);

        // Create collection
        $collection = new TranslationCollection($paginated);
        $array = $collection->toArray(request());

        // Assert structure
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('meta', $array);

        // Assert meta structure
        $this->assertArrayHasKey('total', $array['meta']);
        $this->assertArrayHasKey('per_page', $array['meta']);
        $this->assertArrayHasKey('current_page', $array['meta']);
        $this->assertArrayHasKey('last_page', $array['meta']);
        $this->assertArrayHasKey('from', $array['meta']);
        $this->assertArrayHasKey('to', $array['meta']);

        // Assert meta values
        $this->assertEquals(3, $array['meta']['total']);
        $this->assertEquals(2, $array['meta']['per_page']);
        $this->assertEquals(1, $array['meta']['current_page']);
        $this->assertEquals(2, $array['meta']['last_page']);
        $this->assertEquals(1, $array['meta']['from']);
        $this->assertEquals(2, $array['meta']['to']);

        // Assert data count
        $this->assertCount(2, $array['data']);
    }

    public function test_collection_handles_empty_results(): void
    {
        // Get empty paginated results
        $paginated = Translation::with('tags')->paginate(15);

        // Create collection
        $collection = new TranslationCollection($paginated);
        $array = $collection->toArray(request());

        // Assert structure
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('meta', $array);

        // Assert empty data
        $this->assertCount(0, $array['data']);

        // Assert meta values for empty results
        $this->assertEquals(0, $array['meta']['total']);
        $this->assertEquals(15, $array['meta']['per_page']);
        $this->assertEquals(1, $array['meta']['current_page']);
        $this->assertEquals(1, $array['meta']['last_page']);
        $this->assertNull($array['meta']['from']);
        $this->assertNull($array['meta']['to']);
    }

    public function test_collection_handles_last_page(): void
    {
        // Create 5 translations
        for ($i = 1; $i <= 5; $i++) {
            Translation::create([
                'key' => "test.key{$i}",
                'locale' => 'en',
                'content' => "Content {$i}",
            ]);
        }

        // Get second page with 3 per page
        $paginated = Translation::with('tags')->paginate(3, ['*'], 'page', 2);

        // Create collection
        $collection = new TranslationCollection($paginated);
        $array = $collection->toArray(request());

        // Assert meta values for last page
        $this->assertEquals(5, $array['meta']['total']);
        $this->assertEquals(3, $array['meta']['per_page']);
        $this->assertEquals(2, $array['meta']['current_page']);
        $this->assertEquals(2, $array['meta']['last_page']);
        $this->assertEquals(4, $array['meta']['from']);
        $this->assertEquals(5, $array['meta']['to']);

        // Assert data count (only 2 items on last page)
        $this->assertCount(2, $array['data']);
    }
}
