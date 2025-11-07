<?php
namespace Database\Seeders;

use Database\Factories\AiCategoryFactory;
use Illuminate\Database\Seeder;

class AiCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 main categories
        $parents = AiCategoryFactory::new ()->count(10)->create();

        // For each parent, create 1 subcategories
        $parents->each(function ($parent) {
            AiCategoryFactory::new ()->create([
                'parent_id' => $parent->id,
            ]);
        });
    }
}
