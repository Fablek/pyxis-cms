<?php

namespace Database\Factories;

use App\Models\FieldGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FieldGroup>
 */
class FieldGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'fields' => [],
            'rules' => [],
            'is_active' => true,
        ];
    }
}
