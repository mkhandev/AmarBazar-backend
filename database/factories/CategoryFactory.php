<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Gas Stove Parts & Accessories',
            'Trunks & Boxers',
            'Gas Stoves',
            'Cooktops & Ranges',
            'Music & Sound',
            'Natural Sweeteners',
            'Hoses & Pipes',
            'Cheese Tools',
            'Kitchen Fittings',
            'Womens Fashion',
            'Ovens',
            'Goat',
            'Watches and Accessories',
            'Watering Systems & Garden Hoses',
            'Pools',
            'Gimbals & Stabilizers',
        ];

        $name = $this->faker->unique()->randomElement($categories);

        //$name = $this->faker->unique()->word();

        // Replace spaces with %20 or \n for multi-line
        $text = urlencode(str_replace(' ', '\n', $name));

        $bgColor = $this->faker->hexcolor(); // e.g., #ff5733
        $bgColor = ltrim($bgColor, '#');     // remove #

        return [
            'name'        => ucfirst($name),
            'slug'        => Str::slug($name),
            'description' => $this->faker->sentence(),
            //'image'       => $this->faker->imageUrl(640, 480, 'categories', true),
            'image'       => "https://placehold.co/600x400/{$bgColor}/ffffff?text={$text}",
            'parent_id' => null,
        ];
    }
}
