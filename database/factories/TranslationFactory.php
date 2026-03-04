<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt'];
        $contexts = ['auth', 'common', 'errors', 'validation', 'messages', 'navigation'];
        $actions = ['login', 'logout', 'submit', 'cancel', 'save', 'delete', 'edit', 'create'];
        
        $context = fake()->randomElement($contexts);
        $action = fake()->randomElement($actions);
        $suffix = fake()->randomElement(['title', 'description', 'button', 'label', 'placeholder', 'message']);
        
        return [
            'key' => "{$context}.{$action}.{$suffix}",
            'locale' => fake()->randomElement($locales),
            'content' => fake()->sentence(),
        ];
    }
}
