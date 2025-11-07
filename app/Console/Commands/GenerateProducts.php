<?php
namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'app:generate-products';
    //
    protected $signature = 'generate:products{categoryIndex=0}{product=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate products with images from Unsplash with specific category';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categoryIndex = (int) $this->argument('categoryIndex');
        $productCount  = (int) $this->argument('product');
        $accessKey     = env('UNSPLASH_ACCESS_KEY');

        $categories = [
            //'Clothing'            => ['T-Shirt', 'Jeans', 'Jacket'],
            'Clothing'            => ['T-Shirt', 'Jeans', 'Jacket', 'Sweater', 'Shorts', 'Dress', 'Skirt', 'Hoodie', 'Socks', 'Hat', 'Blouse', 'Coat', 'Cardigan', 'Tank Top', 'Leggings', 'Scarf', 'Gloves', 'Belt', 'Shoes', 'Sneakers', 'Boots', 'Cap', 'Tie', 'Pajamas', 'Swimsuit'],
            'Electronics'         => ['Laptop', 'Smartphone', 'Headphones', 'Monitor', 'Keyboard', 'Mouse', 'Tablet', 'Camera', 'Speaker', 'Charger', 'Router', 'USB Drive', 'Printer', 'Scanner', 'Smartwatch', 'VR Headset', 'Earbuds', 'Projector', 'Microphone', 'Hard Drive', 'Power Bank', 'Drone', 'Smart Home Hub', 'Game Console', 'Webcam'],
            'Furniture'           => ['Desk', 'Chair', 'Table', 'Couch', 'Bed', 'Wardrobe', 'Shelf', 'Cabinet', 'Stool', 'Dresser', 'Nightstand', 'Bookshelf', 'Bench', 'Ottoman', 'Futon', 'Armchair', 'Side Table', 'Dining Table', 'Coffee Table', 'TV Stand', 'Chest', 'Cupboard', 'Hutch', 'Console Table', 'Drawer'],
            'Accessories'         => ['Backpack', 'Watch', 'Wallet', 'Belt', 'Sunglasses', 'Necklace', 'Bracelet', 'Ring', 'Hat', 'Scarf', 'Gloves', 'Earrings', 'Tie', 'Cufflinks', 'Bag', 'Keychain', 'Cap', 'Hairband', 'Brooch', 'Socks', 'Shoes', 'Umbrella', 'Handkerchief', 'Wallet Chain', 'Lanyard'],
            'Toys'                => ['Action Figure', 'Doll', 'Puzzle', 'Board Game', 'Lego Set', 'Plush Toy', 'Yo-Yo', 'RC Car', 'Building Blocks', 'Toy Train', 'Fidget Spinner', 'Stuffed Animal', 'Play-Doh', 'Water Gun', 'Kite', 'Card Game', 'Toy Soldier', 'Puzzle Cube', 'Rubber Duck', 'Marble Set', 'Mini Car', 'Toy Robot', 'Dart Board', 'Jump Rope', 'Scooter'],
            'Beauty'              => ['Lipstick', 'Foundation', 'Eyeliner', 'Mascara', 'Perfume', 'Nail Polish', 'Moisturizer', 'Shampoo', 'Conditioner', 'Face Mask', 'Blush', 'Concealer', 'Hair Oil', 'Body Lotion', 'Sunscreen', 'Lip Balm', 'Hair Spray', 'Hair Straightener', 'Curling Iron', 'Toothpaste', 'Body Wash', 'Bath Bomb', 'Facial Cleanser', 'Eye Cream', 'Hand Cream'],
            'Gadgets'             => ['Smartwatch', 'Fitness Tracker', 'Drone', 'VR Headset', 'Bluetooth Speaker', 'Power Bank', 'Earbuds', 'Camera', 'Projector', 'Smart Home Hub', 'Smart Glasses', 'Action Camera', 'Digital Pen', '3D Printer', 'E-Reader', 'Smart Thermostat', 'GPS Tracker', 'Portable Monitor', 'Wireless Charger', 'Webcam', 'Security Camera', 'Laser Pointer', 'Mini Projector', 'Robot Vacuum', 'Smart Lock'],
            'Food'                => ['Chocolate', 'Cookies', 'Snack Pack', 'Cereal', 'Tea', 'Coffee', 'Pasta', 'Sauce', 'Candy', 'Juice', 'Bread', 'Rice', 'Cheese', 'Milk', 'Butter', 'Jam', 'Honey', 'Yogurt', 'Soda', 'Chips', 'Peanut Butter', 'Nuts', 'Oats', 'Soup', 'Ice Cream'],
            'Sports'              => ['Football', 'Basketball', 'Tennis Racket', 'Cricket Bat', 'Golf Club', 'Yoga Mat', 'Dumbbells', 'Helmet', 'Running Shoes', 'Jersey', 'Gloves', 'Skates', 'Treadmill', 'Jump Rope', 'Cycling Shoes', 'Fitness Ball', 'Resistance Bands', 'Hockey Stick', 'Ping Pong Paddle', 'Soccer Ball', 'Water Bottle', 'Caps', 'Wristband', 'Knee Pads', 'Socks'],
            'Stationery'          => ['Notebook', 'Pen', 'Pencil', 'Eraser', 'Marker', 'Highlighter', 'Stapler', 'Paper Clips', 'Scissors', 'Ruler', 'Glue Stick', 'Sticky Notes', 'Envelope', 'File Folder', 'Calculator', 'Binder', 'Sharpener', 'Notebook Set', 'Drawing Pad', 'Pen Holder', 'Sketchbook', 'Whiteboard', 'Clipboard', 'Stamp', 'Label'],
            'Automotive'          => ['Car Tire', 'Car Battery', 'Engine Oil', 'Car Mat', 'Headlight', 'Brake Pad', 'Car Seat Cover', 'Windshield Wiper', 'Air Freshener', 'Car Cover', 'Steering Wheel Cover', 'Car Jack', 'Car Horn', 'Spark Plug', 'Fuel Filter', 'Car Light', 'Car Cleaner', 'Tire Inflator', 'Seat Belt', 'GPS Navigator', 'Car Charger', 'Tool Kit', 'Car Antenna', 'Mud Flap', 'Roof Rack'],
            'Home Appliances'     => ['Refrigerator', 'Washing Machine', 'Microwave', 'Oven', 'Dishwasher', 'Toaster', 'Blender', 'Coffee Maker', 'Air Conditioner', 'Fan', 'Vacuum Cleaner', 'Water Heater', 'Rice Cooker', 'Juicer', 'Iron', 'Mixer', 'Food Processor', 'Heater', 'Electric Kettle', 'Grill', 'Air Purifier', 'Deep Fryer', 'Slow Cooker', 'Humidifier', 'Water Purifier'],
            'Pet Supplies'        => ['Dog Food', 'Cat Food', 'Bird Seed', 'Pet Bed', 'Dog Leash', 'Cat Toy', 'Pet Bowl', 'Fish Tank', 'Pet Shampoo', 'Pet Collar', 'Dog Toy', 'Pet Carrier', 'Litter Box', 'Pet Treats', 'Aquarium Filter', 'Pet Brush', 'Pet Clothes', 'Pet Cage', 'Pet Bowl Stand', 'Pet Vitamins', 'Bird Cage', 'Pet Nail Clippers', 'Dog House', 'Pet Carrier Bag', 'Pet Fence'],
            'Books'               => ['Novel', 'Science Book', 'Math Book', 'History Book', 'Biography', 'Comic Book', 'Magazine', 'Dictionary', 'Cookbook', 'Travel Guide', 'Poetry', 'Thriller', 'Mystery', 'Fantasy', 'Self Help', 'Romance', 'Science Fiction', 'Children Book', 'Graphic Novel', 'Textbook', 'Encyclopedia', 'Art Book', 'Photography Book', 'Journal', 'Planner'],
            'Musical Instruments' => ['Guitar', 'Piano', 'Violin', 'Drum', 'Flute', 'Saxophone', 'Trumpet', 'Harmonica', 'Keyboard', 'Cello', 'Tambourine', 'Banjo', 'Ukulele', 'Accordion', 'Clarinet', 'Electric Guitar', 'Bass Guitar', 'Drum Kit', 'Maracas', 'Triangle', 'Oboe', 'Trombone', 'Xylophone', 'Synthesizer', 'Recorder'],
            'Health'              => ['Vitamins', 'Pain Reliever', 'Cough Syrup', 'Bandage', 'Thermometer', 'First Aid Kit', 'Hand Sanitizer', 'Antibiotic Cream', 'Glucose Meter', 'Blood Pressure Monitor', 'Inhaler', 'Eye Drops', 'Supplements', 'Protein Powder', 'Face Mask', 'Sanitary Napkin', 'Toothbrush', 'Toothpaste', 'Floss', 'Mouthwash', 'Shampoo', 'Conditioner', 'Moisturizer', 'Sunscreen', 'Hand Cream'],
            'Jewelry'             => ['Necklace', 'Ring', 'Bracelet', 'Earrings', 'Watch', 'Pendant', 'Brooch', 'Cufflinks', 'Anklet', 'Bangle', 'Charm', 'Tiara', 'Hairpin', 'Chain', 'Choker', 'Locket', 'Wedding Ring', 'Engagement Ring', 'Pearl Necklace', 'Gemstone Ring', 'Bracelet Set', 'Ring Set', 'Earring Set', 'Gold Ring', 'Silver Ring'],
            'Garden'              => ['Lawn Mower', 'Garden Hose', 'Shovel', 'Rake', 'Pruner', 'Wheelbarrow', 'Gloves', 'Planter', 'Seeds', 'Fertilizer', 'Garden Soil', 'Watering Can', 'Garden Light', 'Sprinkler', 'Garden Bench', 'Hedge Trimmer', 'Garden Scissors', 'Compost Bin', 'Garden Net', 'Trellis', 'Garden Table', 'Garden Chair', 'Garden Statue', 'Plant Pot', 'Bird Feeder'],
            'Office Supplies'     => ['Chair', 'Desk', 'Lamp', 'File Cabinet', 'Stapler', 'Printer', 'Paper', 'Pen', 'Marker', 'Whiteboard', 'Notebook', 'Binder', 'Envelope', 'Tape', 'Calculator', 'Scissors', 'Folder', 'Desk Organizer', 'Paperweight', 'Label Maker', 'Clock', 'Trash Can', 'Bulletin Board', 'Bookstand', 'Stamp'],
            'Footwear'            => ['Sneakers', 'Running Shoes', 'Formal Shoes', 'Loafers', 'Sandals', 'Flip Flops', 'Slippers', 'Boots', 'Ankle Boots', 'High Heels', 'Wedges', 'Ballet Flats', 'Espadrilles', 'Clogs', 'Moccasins', 'Derby Shoes', 'Oxfords', 'Hiking Boots', 'Work Boots', 'Rain Boots', 'Soccer Cleats', 'Basketball Shoes', 'Tennis Shoes', 'Slides', 'Casual Shoes'],
        ];

        $categoryKeys = array_keys($categories);

        if ($categoryIndex < 1 || $categoryIndex > count($categoryKeys)) {
            $this->error("Invalid category index. Please use a number between 1 and " . count($categoryKeys));
            return;
        }

        $selectedCategoryName = $categoryKeys[$categoryIndex - 1];
        $generateProduct      = $categories[$selectedCategoryName];

        $this->info("Selected Category: {$selectedCategoryName}");
        $this->info("Generating {$productCount} products...");

        $categoryId = Category::where('name', $selectedCategoryName)->first()->id;
        $users      = User::pluck('id')->toArray();
        $faker      = Faker::create();

        DB::beginTransaction();

        foreach ($generateProduct as $productName) {

            $exists = Product::where('name', $productName)
                ->where('category_id', $categoryId)
                ->exists();

            if ($exists) {
                $this->warn("Skipped (duplicate): {$productName} already exists in category ID {$categoryId}");
                continue; // Skip this loop iteration
            }

            try {

                $slug = Str::slug($productName);

                $counter = 1;
                $pSlug   = $slug;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $pSlug . '-' . $counter;
                    $counter++;
                }

                $productModel = Product::create([
                    'name'        => $productName,
                    'slug'        => $slug,
                    'description' => $faker->paragraph(),
                    'price'       => rand(50, 1000),
                    'stock'       => rand(1, 50),
                    'brand'       => $faker->company(),
                    'rating'      => rand(0, 5),
                    'num_reviews' => rand(0, 100),
                    'category_id' => $categoryId,
                    'user_id'     => $users[array_rand($users)],
                    'status'      => true,
                ]);

                $productId = $productModel->id;
                $imageUrls = $this->getUnsplashImages($productModel->name, mt_rand(3, 7));

                if (empty($imageUrls)) {
                    throw new \Exception("No images found for {$productModel->name}");
                }

                // print_r($imageUrls);
                // exit;

                $main            = 0;
                $productImages   = [];
                $downloadedFiles = [];

                foreach ($imageUrls as $index => $url) {
                    $response = Http::get($url);

                    if (! $response->successful()) {
                        throw new \Exception("Image download failed for {$productModel->name}");
                    }

                    $imageContents = $response->body();
                    if (empty($imageContents)) {
                        throw new \Exception("Empty image content for {$productModel->name}");
                    }

                    $filename = Str::slug($productModel->name) . '-' . uniqid() . '.jpg';
                    $path     = "products/{$filename}";

                    Storage::disk('public')->put($path, $imageContents);
                    $imageUrl = Storage::url($path);

                    $productImages[] = [
                        'product_id' => $productModel->id,
                        'image'      => $imageUrl,
                        'is_main'    => $index === 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $downloadedFiles[] = $path;
                }

                ProductImage::insert($productImages);

                DB::commit();
                $this->info("Product added: {$productModel->name}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Product skipped: {$productName} - " . $e->getMessage());
                $this->error("Skipped category: {$categoryId} product: {$productName}");
            }
        }

        //print_r($generateProduct);
    }

    private function getUnsplashImages(string $query, int $count = 5, int $width = 600, int $height = 400): array
    {
        $accessKey = env('UNSPLASH_ACCESS_KEY');
        $imageUrls = [];

        try {
            if (! $accessKey) {
                throw new \Exception("Unsplash access key missing in .env");
            }

            $response = Http::get('https://api.unsplash.com/search/photos', [
                'query'     => $query,
                'per_page'  => $count,
                'order_by'  => 'relevant',
                'client_id' => $accessKey,
            ]);

            $data = $response->json();

            if (empty($data['results'])) {
                //echo "cccc";exit;
                throw new \Exception("No Unsplash results found for {$query}");
            }

            foreach ($data['results'] as $result) {
                $rawUrl = $result['urls']['raw'] ?? null;

                if ($rawUrl) {
                    $imageUrls[] = "{$rawUrl}&w={$width}&h={$height}&fit=crop";
                }
            }
        } catch (\Exception $e) {
            Log::error("Unsplash API error for '{$query}': " . $e->getMessage());
        }

        // print_r($imageUrls);
        // exit;

        return $imageUrls;
    }

}
