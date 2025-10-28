<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //'product_id'  => Product::inRandomOrder()->first()->id ?? Product::factory(),
            //'user_id'     => User::inRandomOrder()->first()->id ?? User::factory(),
            'title'       => $this->faker->sentence(4),
            'rating'      => $this->faker->randomFloat(2, 1, 5), // e.g. 4.50
            'comment'     => $this->faker->paragraph(),
            'is_approved' => true,
        ];
    }
}
