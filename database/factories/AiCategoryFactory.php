<?php
namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class AiCategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Clothing',
            'Electronics',
            'Furniture',
            'Accessories',
            'Toys',
            'Beauty',
            'Gadgets',
            'Food',
            'Sports',
            'Stationery',
            'Automotive',
            'Home Appliances',
            'Pet Supplies',
            'Books',
            'Musical Instruments',
            'Health',
            'Jewelry',
            'Garden',
            'Office Supplies',
            'Footwear',
        ];

        $name = $this->faker->unique()->randomElement($categories);

        $slug = Str::slug($name);

        // Ensure slug is unique in DB
        $counter  = 1;
        $baseSlug = $slug;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Replace spaces with %20 or \n for multi-line
        $text = urlencode(str_replace(' ', '\n', $name));

        $bgColor = $this->faker->hexcolor();
        $bgColor = ltrim($bgColor, '#'); // remove #

        //$imageUrl  = $this->getUnsplashImage($name);
        $imagePath = $this->downloadUnsplashImage($name);

        return [
            'name'        => ucfirst($name),
            'slug'        => $slug,
            'description' => $this->faker->sentence(),
            //'image'       => "https://placehold.co/600x400/{$bgColor}/ffffff?text={$text}",
            'image'       => $imagePath,
            'parent_id'   => null,
        ];
    }

    private function downloadUnsplashImage(string $category, int $width = 600, int $height = 400): string
    {
        //$accessKey = env('UNSPLASH_ACCESS_KEY');
        //$accessKey = "kyzzqopmoIM7on2ZD2JIkao-P5OLMNWIP9eEGpEYbGs";
        $accessKey = config('custom.unsplash_access_key');

        try {
            if (! $accessKey) {
                throw new \Exception("Unsplash access key missing in .env");
            }

            // Call Unsplash API
            $response = Http::get('https://api.unsplash.com/search/photos', [
                'query'     => $category,
                'per_page'  => 1,
                'order_by'  => 'relevant',
                'client_id' => $accessKey,
            ]);

            $data = $response->json();

            if (! isset($data['results'][0]['urls']['raw'])) {
                throw new \Exception("No image found for category {$category}");
            }

            // Get image raw URL and add size parameters
            $imageUrl = $data['results'][0]['urls']['raw'] . "&w={$width}&h={$height}&fit=crop";

            // Download the image content
            $imageContents = Http::get($imageUrl)->body();

            // Create filename and save in storage/app/public/categories
            $filename = Str::slug($category) . '-' . time() . '.jpg';
            $path     = "categories/{$filename}";
            Storage::disk('public')->put($path, $imageContents);

            // Return publicly accessible URL
            return Storage::url($path);
        } catch (\Exception $e) {
            Log::error("Failed to download Unsplash image for category '{$category}': " . $e->getMessage());
            return '/images/default-category.jpg';
        }
    }

    private function getUnsplashImage(string $category, int $width = 600, int $height = 400): string
    {
        $accessKey = env('UNSPLASH_ACCESS_KEY');
        if (! $accessKey) {
            throw new \Exception("Unsplash access key missing in .env");
        }

        try {
            $response = Http::get('https://api.unsplash.com/search/photos', [
                'query'     => $category,
                'per_page'  => 1,
                'client_id' => $accessKey,
            ]);

            $data = $response->json();

            if (isset($data['results'][0]['urls']['raw'])) {
                // Construct URL with width/height
                return $data['results'][0]['urls']['raw'] . "&w={$width}&h={$height}&fit=crop";
            }

            throw new \Exception("No image found for category {$category}");
        } catch (\Exception $e) {
            // Fail if Unsplash request fails
            throw new \Exception("Unsplash API error: " . $e->getMessage());
        }
    }
}
