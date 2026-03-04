<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255);
            $table->string('locale', 2);
            $table->text('content');
            $table->timestamps();

            // Unique constraint on key + locale combination
            $table->unique(['key', 'locale']);

            // Indexes for performance
            $table->index('key');
            $table->index('locale');
            $table->index(['key', 'locale']);
        });

        // GIN index for full-text search on content (PostgreSQL specific)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX idx_translations_content_gin ON translations USING GIN(to_tsvector(\'english\', content))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
