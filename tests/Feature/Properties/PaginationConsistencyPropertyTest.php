<?php

namespace Tests\Feature\Properties;

use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Test for Pagination Consistency
 * 
 * Feature: translation-management-api
 * Property 3: Pagination Consistency
 * 
 * **Validates: Requirements 1.6**
 * 
 * Property Statement:
 * For any dataset of translations and any valid page size, the total number 
 * of translations across all pages should equal the total count, and no 
 * translation should appear on multiple pages or be missing.
 */
class PaginationConsistencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property Test: Total items across all pages equals total count
     * 
     * Tests that when paginating through all pages, the sum of items
     * across all pages equals the total count reported in metadata.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_total_items_across_pages_equals_total_count(): void
    {
        $iterations = 50;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random dataset size (1 to 100 translations)
            $totalTranslations = rand(1, 100);

            // Create translations
            $translations = [];
            for ($j = 0; $j < $totalTranslations; $j++) {
                $translations[] = Translation::create([
                    'key' => "test.key.{$i}.{$j}",
                    'locale' => fake()->randomElement(['en', 'fr', 'es']),
                    'content' => fake()->sentence(),
                ]);
            }

            // Calculate expected number of pages
            $expectedPages = (int) ceil($totalTranslations / $perPage);

            // Collect all items from all pages
            $collectedItems = [];
            $reportedTotal = null;

            for ($page = 1; $page <= $expectedPages; $page++) {
                $response = $this->getJson("/api/translations?page={$page}");
                
                $this->assertEquals(
                    200,
                    $response->status(),
                    "Iteration {$i}: Page {$page} should return 200"
                );

                $data = $response->json();
                
                // Store reported total from first page
                if ($reportedTotal === null) {
                    $reportedTotal = $data['meta']['total'];
                }

                // Verify total is consistent across all pages
                $this->assertEquals(
                    $reportedTotal,
                    $data['meta']['total'],
                    "Iteration {$i}: Total count should be consistent across all pages"
                );

                // Collect items from this page
                foreach ($data['data'] as $item) {
                    $collectedItems[] = $item['id'];
                }

                // Verify page metadata
                $this->assertEquals(
                    $page,
                    $data['meta']['current_page'],
                    "Iteration {$i}: Current page should match requested page"
                );
                
                $this->assertEquals(
                    $perPage,
                    $data['meta']['per_page'],
                    "Iteration {$i}: Per page should be {$perPage}"
                );

                $this->assertEquals(
                    $expectedPages,
                    $data['meta']['last_page'],
                    "Iteration {$i}: Last page should match calculated pages"
                );
            }

            // Verify total collected items equals reported total
            $this->assertCount(
                $totalTranslations,
                $collectedItems,
                "Iteration {$i}: Total collected items should equal total translations created"
            );

            $this->assertEquals(
                $totalTranslations,
                $reportedTotal,
                "Iteration {$i}: Reported total should equal actual total"
            );

            // Clean up for next iteration
            Translation::query()->delete();
        }
    }

    /**
     * Property Test: No duplicate items across pages
     * 
     * Tests that no translation appears on multiple pages when paginating.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_no_duplicate_items_across_pages(): void
    {
        $iterations = 50;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random dataset size (10 to 100 translations)
            $totalTranslations = rand(10, 100);

            // Create translations
            for ($j = 0; $j < $totalTranslations; $j++) {
                Translation::create([
                    'key' => "test.key.{$i}.{$j}",
                    'locale' => fake()->randomElement(['en', 'fr', 'es']),
                    'content' => fake()->sentence(),
                ]);
            }

            // Calculate expected number of pages
            $expectedPages = (int) ceil($totalTranslations / $perPage);

            // Collect all item IDs from all pages
            $allItemIds = [];

            for ($page = 1; $page <= $expectedPages; $page++) {
                $response = $this->getJson("/api/translations?page={$page}");
                
                $this->assertEquals(200, $response->status());

                $data = $response->json();
                
                foreach ($data['data'] as $item) {
                    $allItemIds[] = $item['id'];
                }
            }

            // Verify no duplicates
            $uniqueIds = array_unique($allItemIds);
            $this->assertCount(
                count($allItemIds),
                $uniqueIds,
                "Iteration {$i}: No translation should appear on multiple pages (found duplicates)"
            );

            // Clean up for next iteration
            Translation::query()->delete();
        }
    }

    /**
     * Property Test: No missing items across pages
     * 
     * Tests that all translations are included when paginating through all pages.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_no_missing_items_across_pages(): void
    {
        $iterations = 50;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random dataset size (10 to 100 translations)
            $totalTranslations = rand(10, 100);

            // Create translations and track their IDs
            $createdIds = [];
            for ($j = 0; $j < $totalTranslations; $j++) {
                $translation = Translation::create([
                    'key' => "test.key.{$i}.{$j}",
                    'locale' => fake()->randomElement(['en', 'fr', 'es']),
                    'content' => fake()->sentence(),
                ]);
                $createdIds[] = $translation->id;
            }

            // Calculate expected number of pages
            $expectedPages = (int) ceil($totalTranslations / $perPage);

            // Collect all item IDs from all pages
            $collectedIds = [];

            for ($page = 1; $page <= $expectedPages; $page++) {
                $response = $this->getJson("/api/translations?page={$page}");
                
                $this->assertEquals(200, $response->status());

                $data = $response->json();
                
                foreach ($data['data'] as $item) {
                    $collectedIds[] = $item['id'];
                }
            }

            // Sort both arrays for comparison
            sort($createdIds);
            sort($collectedIds);

            // Verify all created IDs are in collected IDs
            $this->assertEquals(
                $createdIds,
                $collectedIds,
                "Iteration {$i}: All created translations should be present in paginated results"
            );

            // Clean up for next iteration
            Translation::query()->delete();
        }
    }

    /**
     * Property Test: Page item counts are correct
     * 
     * Tests that each page contains the correct number of items,
     * with the last page potentially having fewer items.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_page_item_counts_are_correct(): void
    {
        $iterations = 50;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random dataset size (5 to 100 translations)
            $totalTranslations = rand(5, 100);

            // Create translations
            for ($j = 0; $j < $totalTranslations; $j++) {
                Translation::create([
                    'key' => "test.key.{$i}.{$j}",
                    'locale' => fake()->randomElement(['en', 'fr', 'es']),
                    'content' => fake()->sentence(),
                ]);
            }

            // Calculate expected number of pages
            $expectedPages = (int) ceil($totalTranslations / $perPage);

            for ($page = 1; $page <= $expectedPages; $page++) {
                $response = $this->getJson("/api/translations?page={$page}");
                
                $this->assertEquals(200, $response->status());

                $data = $response->json();
                $itemCount = count($data['data']);

                if ($page < $expectedPages) {
                    // All pages except the last should have exactly perPage items
                    $this->assertEquals(
                        $perPage,
                        $itemCount,
                        "Iteration {$i}: Page {$page} should have exactly {$perPage} items"
                    );
                } else {
                    // Last page should have remaining items
                    $expectedLastPageItems = $totalTranslations - (($expectedPages - 1) * $perPage);
                    $this->assertEquals(
                        $expectedLastPageItems,
                        $itemCount,
                        "Iteration {$i}: Last page {$page} should have {$expectedLastPageItems} items"
                    );
                }

                // Verify from/to metadata
                if ($itemCount > 0) {
                    $expectedFrom = (($page - 1) * $perPage) + 1;
                    $expectedTo = min($page * $perPage, $totalTranslations);
                    
                    $this->assertEquals(
                        $expectedFrom,
                        $data['meta']['from'],
                        "Iteration {$i}: Page {$page} 'from' should be {$expectedFrom}"
                    );
                    
                    $this->assertEquals(
                        $expectedTo,
                        $data['meta']['to'],
                        "Iteration {$i}: Page {$page} 'to' should be {$expectedTo}"
                    );
                }
            }

            // Clean up for next iteration
            Translation::query()->delete();
        }
    }

    /**
     * Property Test: Empty dataset pagination is consistent
     * 
     * Tests that pagination works correctly with an empty dataset.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_empty_dataset_pagination_is_consistent(): void
    {
        $iterations = 20;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Request first page with no data
            $response = $this->getJson("/api/translations?page=1");
            
            $this->assertEquals(200, $response->status());

            $data = $response->json();

            // Verify empty results
            $this->assertCount(
                0,
                $data['data'],
                "Iteration {$i}: Empty dataset should return 0 items"
            );

            // Verify metadata for empty results
            $this->assertEquals(
                0,
                $data['meta']['total'],
                "Iteration {$i}: Total should be 0 for empty dataset"
            );
            
            $this->assertEquals(
                1,
                $data['meta']['current_page'],
                "Iteration {$i}: Current page should be 1"
            );
            
            $this->assertEquals(
                1,
                $data['meta']['last_page'],
                "Iteration {$i}: Last page should be 1 for empty dataset"
            );
            
            $this->assertNull(
                $data['meta']['from'],
                "Iteration {$i}: 'from' should be null for empty dataset"
            );
            
            $this->assertNull(
                $data['meta']['to'],
                "Iteration {$i}: 'to' should be null for empty dataset"
            );
            
            $this->assertEquals(
                $perPage,
                $data['meta']['per_page'],
                "Iteration {$i}: Per page should be {$perPage}"
            );
        }
    }

    /**
     * Property Test: Single page dataset is consistent
     * 
     * Tests that pagination works correctly when all items fit on one page.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_single_page_dataset_is_consistent(): void
    {
        $iterations = 50;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random dataset size (1 to 15 translations to fit on one page)
            $totalTranslations = rand(1, 15);

            // Create translations
            for ($j = 0; $j < $totalTranslations; $j++) {
                Translation::create([
                    'key' => "test.key.{$i}.{$j}",
                    'locale' => fake()->randomElement(['en', 'fr', 'es']),
                    'content' => fake()->sentence(),
                ]);
            }

            // Request first page
            $response = $this->getJson("/api/translations?page=1");
            
            $this->assertEquals(200, $response->status());

            $data = $response->json();

            // Verify all items are on first page
            $this->assertCount(
                $totalTranslations,
                $data['data'],
                "Iteration {$i}: All {$totalTranslations} items should be on first page"
            );

            // Verify metadata
            $this->assertEquals(
                $totalTranslations,
                $data['meta']['total'],
                "Iteration {$i}: Total should equal dataset size"
            );
            
            $this->assertEquals(
                1,
                $data['meta']['current_page'],
                "Iteration {$i}: Current page should be 1"
            );
            
            $this->assertEquals(
                1,
                $data['meta']['last_page'],
                "Iteration {$i}: Last page should be 1 when all items fit on one page"
            );
            
            $this->assertEquals(
                1,
                $data['meta']['from'],
                "Iteration {$i}: 'from' should be 1"
            );
            
            $this->assertEquals(
                $totalTranslations,
                $data['meta']['to'],
                "Iteration {$i}: 'to' should equal total items"
            );
            
            $this->assertEquals(
                $perPage,
                $data['meta']['per_page'],
                "Iteration {$i}: Per page should be {$perPage}"
            );

            // Clean up for next iteration
            Translation::query()->delete();
        }
    }

    /**
     * Property Test: Pagination consistency across multiple page requests
     * 
     * Tests that pagination works correctly when requesting the same
     * dataset multiple times with the fixed page size.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_pagination_consistency_across_requests(): void
    {
        $iterations = 30;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random dataset size (20 to 100 translations)
            $totalTranslations = rand(20, 100);

            // Create translations
            $createdIds = [];
            for ($j = 0; $j < $totalTranslations; $j++) {
                $translation = Translation::create([
                    'key' => "test.key.{$i}.{$j}",
                    'locale' => fake()->randomElement(['en', 'fr', 'es']),
                    'content' => fake()->sentence(),
                ]);
                $createdIds[] = $translation->id;
            }

            $expectedPages = (int) ceil($totalTranslations / $perPage);

            // Request all pages twice to verify consistency
            $firstRun = [];
            $secondRun = [];

            for ($page = 1; $page <= $expectedPages; $page++) {
                $response1 = $this->getJson("/api/translations?page={$page}");
                $this->assertEquals(200, $response1->status());
                $data1 = $response1->json();
                
                foreach ($data1['data'] as $item) {
                    $firstRun[] = $item['id'];
                }

                $response2 = $this->getJson("/api/translations?page={$page}");
                $this->assertEquals(200, $response2->status());
                $data2 = $response2->json();
                
                foreach ($data2['data'] as $item) {
                    $secondRun[] = $item['id'];
                }
            }

            // Verify both runs collected the same items
            $this->assertEquals(
                $firstRun,
                $secondRun,
                "Iteration {$i}: Multiple requests should return consistent results"
            );

            // Verify all items collected
            $this->assertCount(
                $totalTranslations,
                $firstRun,
                "Iteration {$i}: Should collect all {$totalTranslations} items"
            );

            // Verify no duplicates
            $uniqueIds = array_unique($firstRun);
            $this->assertCount(
                count($firstRun),
                $uniqueIds,
                "Iteration {$i}: Should have no duplicates"
            );

            // Verify all created IDs are present
            sort($createdIds);
            sort($firstRun);
            $this->assertEquals(
                $createdIds,
                $firstRun,
                "Iteration {$i}: All created IDs should be present"
            );

            // Clean up for next iteration
            Translation::query()->delete();
        }
    }

    /**
     * Property Test: Requesting page beyond last page returns empty results
     * 
     * Tests that requesting a page number beyond the last page returns
     * empty results with correct metadata.
     * 
     * Note: The API uses a fixed page size of 15 items per page.
     */
    public function test_property_requesting_page_beyond_last_returns_empty(): void
    {
        $iterations = 30;
        $perPage = 15; // Fixed page size in the API

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random dataset size (5 to 50 translations)
            $totalTranslations = rand(5, 50);

            // Create translations
            for ($j = 0; $j < $totalTranslations; $j++) {
                Translation::create([
                    'key' => "test.key.{$i}.{$j}",
                    'locale' => fake()->randomElement(['en', 'fr', 'es']),
                    'content' => fake()->sentence(),
                ]);
            }

            // Calculate last page
            $lastPage = (int) ceil($totalTranslations / $perPage);
            
            // Request page beyond last page
            $beyondPage = $lastPage + rand(1, 5);
            
            $response = $this->getJson("/api/translations?page={$beyondPage}");
            
            $this->assertEquals(200, $response->status());

            $data = $response->json();

            // Verify empty results
            $this->assertCount(
                0,
                $data['data'],
                "Iteration {$i}: Page {$beyondPage} beyond last page {$lastPage} should return 0 items"
            );

            // Verify metadata still reports correct total and last page
            $this->assertEquals(
                $totalTranslations,
                $data['meta']['total'],
                "Iteration {$i}: Total should still be correct"
            );
            
            $this->assertEquals(
                $lastPage,
                $data['meta']['last_page'],
                "Iteration {$i}: Last page should still be correct"
            );
            
            $this->assertEquals(
                $beyondPage,
                $data['meta']['current_page'],
                "Iteration {$i}: Current page should reflect requested page"
            );
            
            $this->assertEquals(
                $perPage,
                $data['meta']['per_page'],
                "Iteration {$i}: Per page should be {$perPage}"
            );

            // Clean up for next iteration
            Translation::query()->delete();
        }
    }
}
