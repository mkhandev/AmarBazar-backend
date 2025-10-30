<?php
namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = Category::pluck('id')->toArray();
        $users      = User::pluck('id')->toArray();

        if (empty($categories) || empty($users)) {
            throw new \Exception('Please seed categories and users first.');
        }

        $name = $this->faker->unique()->words(3, true);
        $slug = Str::slug($name);

        return [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $this->faker->paragraph(),
            'price'       => $this->faker->randomFloat(2, 100, 5000),
            'stock'       => $this->faker->numberBetween(1, 100),
            'brand'       => $this->faker->company(),
            'rating'      => $this->faker->randomFloat(2, 0, 5),
            'num_reviews' => $this->faker->numberBetween(0, 100),
            'category_id' => $this->faker->randomElement($categories),
            'user_id'     => $this->faker->randomElement($users),
            'status'      => true,
        ];
    }
}
