<?php
namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $users    = User::all();

        if ($products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Skipping ReviewSeeder: products or users not found.');
            return;
        }

        foreach ($products as $product) {
            Review::factory()
                ->count(rand(2, 5)) // 2 to 5 reviews per product
                ->create([
                    'product_id' => $product->id,
                    'user_id'    => $users->random()->id,
                ]);
        }
    }
}
