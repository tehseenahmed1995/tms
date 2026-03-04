<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTranslationsCommand extends Command
{
    protected $signature = 'translations:seed {count=100000 : Number of translations to create}';
    protected $description = 'Seed the database with translations for performance testing';

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $this->info("Seeding {$count} translations...");

        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'zh', 'ar'];
        $tagNames = ['mobile', 'desktop', 'web', 'api', 'admin', 'user', 'common', 'error', 'success', 'validation'];
        
        // Create tags first
        $this->info('Creating tags...');
        $tags = collect($tagNames)->map(fn($name) => Tag::firstOrCreate(['name' => $name]));
        
        $this->info('Creating translations in batches...');
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);
        
        $bar = $this->output->createProgressBar($batches);
        $bar->start();

        for ($batch = 0; $batch < $batches; $batch++) {
            $currentBatchSize = min($batchSize, $count - ($batch * $batchSize));
            $translations = [];
            
            for ($i = 0; $i < $currentBatchSize; $i++) {
                $index = ($batch * $batchSize) + $i;
                $locale = $locales[$index % count($locales)];
                
                $translations[] = [
                    'key' => "key.{$locale}.{$index}",
                    'locale' => $locale,
                    'content' => "Translation content for {$locale} #{$index}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('translations')->insert($translations);
            
            // Attach random tags to translations
            $translationIds = Translation::latest('id')->take($currentBatchSize)->pluck('id');
            foreach ($translationIds as $translationId) {
                $randomTags = $tags->random(rand(1, 3))->pluck('id');
                DB::table('translation_tag')->insert(
                    $randomTags->map(fn($tagId) => [
                        'translation_id' => $translationId,
                        'tag_id' => $tagId,
                    ])->toArray()
                );
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully seeded {$count} translations!");
        
        return Command::SUCCESS;
    }
}
