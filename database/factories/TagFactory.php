<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $tags = ['mobile', 'web', 'desktop', 'admin', 'public', 'internal', 'api', 'frontend', 'backend'];
        
        return [
            'name' => fake()->unique()->randomElement($tags),
        ];
    }
}
