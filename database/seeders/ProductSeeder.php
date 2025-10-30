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

        $categories = ['product', 'fashion', 'electronics', 'furniture', 'food', 'clothes', 'accessory', 'gadget', 'beauty', 'toy'];

        Product::factory()
            ->count(500)
            ->create()
            ->each(function ($product, $i) use ($faker, $categories) {
                $imageCount = rand(2, 4);
                $category   = $faker->randomElement($categories);

                for ($j = 1; $j <= $imageCount; $j++) {

                    // $text = urlencode(str_replace(' ', '\n', $product->name));
                    // $bgColor = $faker->hexColor();
                    // $bgColor = ltrim($bgColor, '#');

                    $imageUrl = "https://loremflickr.com/600/400/{$category}?lock=" . rand(1, 9999);

                    ProductImage::create([
                        'product_id' => $product->id,
                        //'image'      => "https://placehold.co/600x400/{$bgColor}/ffffff?text={$text}",
                        'image'      => $imageUrl,
                        'is_main'    => $j === 1,
                    ]);
                }
            });
    }
}
