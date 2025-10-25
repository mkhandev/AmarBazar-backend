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
        // Create 5 main categories
        $parents = Category::factory(5)->create();

        // For each parent, create 3 subcategories
        $parents->each(function ($parent) {
            Category::factory(1)->create([
                'parent_id' => $parent->id,
            ]);
        });
    }
}
