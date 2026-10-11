<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'sku' => fake()->unique()->bothify('SKU-########'), 'size' => 'M', 'color' => 'Indigo', 'price' => 125050, 'stock' => 5, 'reserved' => 0];
    }
}
