<?php
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 8 main categories
        $parents = Category::factory(8)->create();

        // For each parent, create 1 subcategories
        $parents->each(function ($parent) {
            Category::factory(1)->create([
                'parent_id' => $parent->id,
            ]);
        });
    }
}
