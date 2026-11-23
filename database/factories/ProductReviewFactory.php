<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductReviewFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'name' => fake()->firstName(), 'rating' => 5, 'body' => fake()->sentence(8), 'status' => 'approved'];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}
