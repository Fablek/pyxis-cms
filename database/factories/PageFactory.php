<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'        => $this->faker->sentence(3),
            'slug'         => $this->faker->unique()->slug(),
            'content'      => null,
            'status'       => 'published',
            'visibility'   => 'public',
            'published_at' => now()->subDay(),
            'user_id'      => \App\Models\User::factory(),
            'seo'          => null,
        ];
    }
}
