<?php
namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        Product::factory()
            ->count(20)
            ->create()
            ->each(function ($product, $i) use ($faker) {
                $imageCount = rand(2, 4);

                for ($j = 1; $j <= $imageCount; $j++) {

                    $text = urlencode(str_replace(' ', '\n', $product->name));

                    $bgColor = $faker->hexColor();
                    $bgColor = ltrim($bgColor, '#');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image'      => "https://placehold.co/600x400/{$bgColor}/ffffff?text={$text}",
                        'is_main' => $j === 1,
                    ]);
                }
            });
    }
}
