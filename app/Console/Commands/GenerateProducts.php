<?php
namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Console\Command;
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

        // $categories = [
        //     'Clothing'    => ['T-Shirt', 'Jeans'],
        //     'Electronics' => ['Laptop', 'Smartphone'],
        //     'Furniture'   => ['Desk', 'Chair', 'Table'],
        // ];

        $categories = [
            'Clothing'            => [
                'Classic Cotton T-Shirt', 'Slim Fit Jeans', 'Leather Jacket', 'Wool Sweater', 'Casual Shorts',
                'Summer Dress', 'Pleated Skirt', 'Hooded Sweatshirt', 'Ankle Socks', 'Baseball Cap',
                'Silk Blouse', 'Trench Coat', 'Buttoned Cardigan', 'Ribbed Tank Top', 'High-Waist Leggings',
                'Cashmere Scarf', 'Leather Gloves', 'Designer Belt', 'Running Shoes', 'Canvas Sneakers',
                'Winter Boots', 'Snapback Cap', 'Silk Tie', 'Cotton Pajamas', 'One-Piece Swimsuit',
            ],
            'Electronics'         => [
                '15-inch Laptop with Retina Display', 'Android Smartphone 128GB', 'Noise-Cancelling Headphones',
                '4K Ultra HD Monitor', 'Mechanical Gaming Keyboard', 'Wireless Optical Mouse', '10-inch Android Tablet',
                'DSLR Camera with Lens Kit', 'Bluetooth Portable Speaker', 'Fast Charging Wall Charger',
                'WiFi Router Dual Band', '128GB USB 3.0 Flash Drive', 'All-in-One Printer', 'Document Scanner',
                'Smartwatch with Heart Rate Monitor', 'VR Headset for Gaming', 'True Wireless Earbuds',
                'HD Projector', 'Professional Microphone', 'External Hard Drive 2TB', 'Portable Power Bank',
                'Camera Drone 4K', 'Smart Home Hub', 'Next-Gen Game Console', 'HD Webcam',
            ],
            'Furniture'           => [
                'Ergonomic Office Desk', 'Leather Executive Chair', 'Solid Wood Dining Table', 'Modern Couch', 'Queen Size Bed',
                'Two-Door Wardrobe', 'Adjustable Shelf Unit', 'Kitchen Storage Cabinet', 'Bar Stool', '6-Drawer Dresser',
                'Bedside Nightstand', 'Tall Bookshelf', 'Wooden Bench', 'Fabric Ottoman', 'Futon Sofa Bed',
                'Recliner Armchair', 'Round Side Table', 'Extendable Dining Table', 'Glass Coffee Table', 'TV Entertainment Stand',
                'Corner Cabinet', 'Wall-Mounted Shelf', 'Folding Chair', 'Patio Table', 'Outdoor Bench',
            ],
            'Accessories'         => [
                'Leather Backpack', 'Digital Watch', 'RFID Wallet', 'Designer Leather Belt', 'Polarized Sunglasses',
                'Gold Necklace', 'Silver Bracelet', 'Diamond Ring', 'Wool Hat', 'Silk Scarf',
                'Leather Gloves', 'Pearl Earrings', 'Silk Tie', 'Cufflinks Set', 'Canvas Shoulder Bag',
                'Keychain Organizer', 'Baseball Cap', 'Elastic Hairband', 'Vintage Brooch', 'Cotton Socks',
                'Charm Bracelet', 'Leather Card Holder', 'Sunglasses Case', 'Neck Gaiter', 'Wool Beanie',
            ],
            'Toys'                => [
                'Superhero Action Figure', 'Barbie Doll', '1000-Piece Jigsaw Puzzle', 'Monopoly Board Game', 'Lego City Set',
                'Plush Teddy Bear', 'Classic Yo-Yo', 'Remote Control Car', 'Wooden Building Blocks', 'Toy Train Set',
                'Fidget Spinner', 'Stuffed Animal Elephant', 'Play-Doh Modeling Kit', 'Water Gun Pistol', 'Colorful Kite',
                'Uno Card Game', 'Toy Soldier Set', 'Rubik’s Cube', 'Rubber Duck Bath Toy', 'Marble Collection Set',
                'Mini Toy Car Set', 'Plastic Dinosaur Toy', 'Magnetic Building Tiles', 'Puzzle Cube', 'Scooter for Kids',
            ],
            'Beauty'              => [
                'Matte Lipstick Set', 'Liquid Foundation', 'Waterproof Eyeliner', 'Volumizing Mascara', 'Floral Perfume',
                'Gel Nail Polish', 'Hydrating Moisturizer', 'Anti-Dandruff Shampoo', 'Smoothing Conditioner', 'Clay Face Mask',
                'Powder Blush', 'Liquid Concealer', 'Argan Hair Oil', 'Body Lotion with Shea Butter', 'SPF 50 Sunscreen',
                'Tinted Lip Balm', 'Hairspray Strong Hold', 'Ceramic Hair Straightener', 'Curling Iron 32mm', 'Fluoride Toothpaste',
                'Face Serum', 'Body Scrub', 'Eye Shadow Palette', 'Makeup Remover', 'Lip Gloss',
            ],
            'Gadgets'             => [
                'Waterproof Smartwatch', 'Fitness Tracker Band', 'Camera Drone with GPS', 'VR Gaming Headset', 'Portable Bluetooth Speaker',
                'High-Capacity Power Bank', 'Wireless Earbuds with Case', '4K Digital Camera', 'Mini Projector', 'Smart Home Hub',
                'Augmented Reality Smart Glasses', 'Action Camera 4K', 'Digital Stylus Pen', 'Desktop 3D Printer', 'E-Reader Tablet',
                'Smart Thermostat', 'GPS Pet Tracker', 'Portable 15-inch Monitor', 'Wireless Fast Charger', 'HD Webcam',
                'Bluetooth Car Adapter', 'LED Desk Lamp', 'Smart Doorbell', 'Noise Cancelling Headset', 'Portable Projector Screen',
            ],
            'Food'                => [
                'Dark Chocolate Bar', 'Assorted Cookies Pack', 'Healthy Snack Pack', 'Breakfast Cereal Box', 'Green Tea Bags',
                'Premium Coffee Beans', 'Whole Wheat Pasta', 'Tomato Pasta Sauce', 'Fruit Candy Mix', 'Fresh Orange Juice',
                'Sourdough Bread Loaf', 'Basmati Rice 5kg', 'Cheddar Cheese Block', 'Organic Milk Bottle', 'Salted Butter Pack',
                'Strawberry Jam', 'Honey Jar', 'Greek Yogurt', 'Soda Can Pack', 'Potato Chips',
                'Peanut Butter Jar', 'Almonds Pack', 'Oats Cereal Box', 'Tomato Soup Can', 'Vanilla Ice Cream Tub',
            ],
            'Sports'              => [
                'Official Size Football', 'Pro Basketball', 'Carbon Tennis Racket', 'Maple Cricket Bat', 'Titanium Golf Club',
                'Eco Yoga Mat', 'Adjustable Dumbbells', 'Bicycle Helmet', 'Running Shoes for Men', 'Team Jersey',
                'Goalkeeper Gloves', 'Inline Skates', 'Home Treadmill', 'Skipping Jump Rope', 'Cycling Shoes',
                'Inflatable Fitness Ball', 'Resistance Bands Set', 'Hockey Stick Pro', 'Ping Pong Paddle', 'Soccer Ball',
                'Sports Water Bottle', 'Baseball Cap', 'Wristband Set', 'Knee Pads', 'Sports Socks',
            ],
            'Stationery'          => [
                'Hardcover Notebook', 'Ballpoint Pen', 'Graphite Pencil Set', 'Rubber Eraser', 'Permanent Marker Set',
                'Highlighter Pens', 'Metal Stapler', 'Pack of Paper Clips', 'Sharp Scissors', '30cm Ruler',
                'Glue Stick Pack', 'Sticky Note Pads', 'Self-Seal Envelopes', 'File Folder Set', 'Scientific Calculator',
                'Ring Binder', 'Pencil Sharpener', 'Art Notebook Set', 'Drawing Pad', 'Desk Pen Holder',
                'Sketchbook Journal', 'Whiteboard 60x90cm', 'Clipboard with Clip', 'Rubber Stamp', 'Label Stickers',
            ],
            'Automotive'          => [
                'All-Season Car Tire', '12V Car Battery', 'Synthetic Engine Oil 5L', 'Rubber Car Mat', 'LED Headlight',
                'Front Brake Pad Set', 'Leather Car Seat Cover', 'Windshield Wiper Blade', 'Car Air Freshener', 'Universal Car Cover',
                'Steering Wheel Cover', 'Hydraulic Car Jack', 'Car Horn Dual Tone', 'Spark Plug Pack', 'Fuel Filter Cartridge',
                'LED Car Light', 'Car Cleaning Kit', 'Tire Inflator Pump', '3-Point Seat Belt', 'GPS Car Navigator',
                'USB Car Charger', 'Car Tool Kit', 'Car Antenna Mast', 'Mud Flap Set', 'Roof Rack Cross Bars',
            ],
            'Home Appliances'     => [
                'Stainless Steel Refrigerator', 'Front Load Washing Machine', 'Microwave Oven 30L', 'Convection Oven', 'Dishwasher 12 Place',
                '2-Slice Toaster', 'High-Speed Blender', 'Coffee Maker Machine', 'Split Air Conditioner 1.5 Ton', 'Table Fan 16-inch',
                'Bagless Vacuum Cleaner', 'Electric Water Heater 10L', 'Rice Cooker 1.8L', 'Juicer Extractor', 'Clothes Iron',
                'Food Processor 3-in-1', 'Ceramic Heater', 'Electric Kettle 1.7L', 'Indoor Grill', 'Air Purifier HEPA',
                'Slow Cooker 6L', 'Humidifier Ultrasonic', 'TV Remote Controllers', 'Sandwich Maker', 'Electric Pressure Cooker',
            ],
            'Pet Supplies'        => [
                'Premium Dog Food 5kg', 'Cat Food Pack 2kg', 'Bird Seed Mix', 'Orthopedic Pet Bed', 'Retractable Dog Leash',
                'Interactive Cat Toy', 'Stainless Steel Pet Bowl', 'Aquarium Fish Tank 50L', 'Pet Shampoo 500ml', 'Leather Pet Collar',
                'Rubber Dog Toy', 'Travel Pet Carrier', 'Litter Box with Scoop', 'Healthy Pet Treats', 'Aquarium Filter Pump',
                'Pet Grooming Brush', 'Cute Pet Clothes', 'Small Pet Cage', 'Elevated Pet Bowl Stand', 'Multivitamin for Pets',
                'Bird Cage with Stand', 'Pet Nail Clippers', 'Wooden Dog House', 'Pet Carrier Bag', 'Portable Pet Fence',
            ],
            'Books'               => [
                'Bestselling Novel', 'Physics Science Book', 'Advanced Math Textbook', 'World History Book', 'English Literature',
                'Marvel Comic Book', 'Monthly Magazine Issue', 'English Dictionary', 'Italian Cookbook', 'Travel Guide to Europe',
                'Poetry Collection', 'Suspense Thriller Novel', 'Mystery Detective Book', 'Fantasy Adventure Novel', 'Self-Help Guide',
                'Romance Story Book', 'Science Fiction Saga', 'Children Picture Book', 'Graphic Novel Series', 'Encyclopedia Set',
                'Art Coffee Table Book', 'Photography Techniques Book', 'Daily Journal Notebook', '2025 Planner', 'Educational Workbook',
            ],
            'Musical Instruments' => [
                'Acoustic Guitar', '88-Key Digital Piano', 'Full Size Violin', 'Drum Kit with Cymbals', 'Wooden Flute',
                'Alto Saxophone', 'Brass Trumpet', 'Harmonica Set', 'Electronic Keyboard', 'Cello with Bow',
                'Tambourine Percussion', 'Banjo 5-String', 'Ukulele Soprano', 'Accordion Instrument', 'B-flat Clarinet',
                'Electric Guitar Pack', 'Bass Guitar 4-String', 'Professional Drum Kit', 'Maracas Pair', 'Triangle Percussion',
                'Oboe Instrument', 'Trombone Brass', 'Xylophone Set', 'Synthesizer Keyboard', 'Recorder Flute',
            ],
            'Health'              => [
                'Vitamin C Tablets', 'Pain Relief Capsules', 'Cough Syrup 100ml', 'Sterile Bandage Pack', 'Digital Thermometer',
                'First Aid Kit Box', 'Hand Sanitizer Gel', 'Antibiotic Cream Tube', 'Blood Glucose Meter', 'Blood Pressure Monitor',
                'Asthma Inhaler', 'Lubricating Eye Drops', 'Dietary Supplements', 'Whey Protein Powder', 'Face Mask Pack',
                'Sanitary Napkins', 'Electric Toothbrush', 'Fluoride Toothpaste', 'Dental Floss Pack', 'Mint Mouthwash',
                'Moisturizing Shampoo', 'Conditioner for Dry Hair', 'Hand Cream Tube', 'SPF Sunscreen Lotion', 'Moisturizer Stick',
            ],
            'Jewelry'             => [
                'Gold Necklace with Pendant', 'Diamond Engagement Ring', 'Sterling Silver Bracelet', 'Stud Earrings Set', 'Luxury Watch',
                'Heart-Shaped Pendant', 'Vintage Brooch', 'Men Cufflinks Set', 'Anklet Chain', 'Bangle Bracelet',
                'Charm Bracelet', 'Tiara Crown', 'Hairpin Accessories', 'Gold Chain Necklace', 'Velvet Choker',
                'Lockable Locket', 'Wedding Ring Set', 'Engagement Ring with Diamond', 'Pearl Necklace', 'Gemstone Ring',
                'Bracelet Set', 'Ring Set', 'Earring Set', 'Gold Band Ring', 'Silver Gem Ring',
            ],
            'Garden'              => [
                'Electric Lawn Mower', 'Expandable Garden Hose', 'Steel Shovel', 'Plastic Leaf Rake', 'Tree Pruner',
                'Wheelbarrow Cart', 'Gardening Gloves Pair', 'Ceramic Plant Planter', 'Vegetable Seeds Pack', 'Organic Fertilizer Bag',
                'Potting Garden Soil', 'Watering Can 2L', 'Solar Garden Light', 'Sprinkler System', 'Outdoor Garden Bench',
                'Hedge Trimmer Electric', 'Garden Scissors Set', 'Compost Bin', 'Garden Safety Net', 'Wooden Trellis',
                'Garden Table Set', 'Folding Garden Chair', 'Decorative Garden Statue', 'Clay Plant Pot', 'Bird Feeder Station',
            ],
            'Office Supplies'     => [
                'Ergonomic Office Chair', 'Wooden Desk', 'LED Desk Lamp', 'Metal File Cabinet', 'Heavy-Duty Stapler',
                'Laser Printer', 'A4 Paper Pack', 'Gel Ink Pen', 'Permanent Marker Set', 'Magnetic Whiteboard',
                'Hardcover Notebook', 'Ring Binder', 'Envelope Pack', 'Transparent Tape Roll', 'Scientific Calculator',
                'Precision Scissors', 'Plastic Folder', 'Desk Organizer Tray', 'Paperweight Crystal', 'Label Maker',
                'Wall Clock', 'Trash Can 10L', 'Bulletin Board Cork', 'Bookstand Holder', 'Rubber Stamp',
            ],
            'Footwear'            => [
                'Men Running Sneakers', 'Women Running Shoes', 'Leather Formal Shoes', 'Classic Loafers', 'Summer Sandals',
                'Flip Flops Pair', 'Indoor Slippers', 'Winter Boots', 'Ankle Boots', 'High Heel Pumps',
                'Wedge Sandals', 'Ballet Flats Pair', 'Espadrilles Shoes', 'Leather Clogs', 'Suede Moccasins',
                'Derby Shoes', 'Oxford Leather Shoes', 'Hiking Boots', 'Work Safety Boots', 'Rain Boots',
                'Soccer Cleats', 'Basketball Shoes', 'Tennis Shoes', 'Slide Sandals', 'Casual Walking Shoes',
            ],
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

        foreach ($generateProduct as $productName) {

            //DB::beginTransaction();

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

                $imageUrls = $this->getUnsplashImages($productName, mt_rand(3, 7));

                if (empty($imageUrls)) {
                    $this->warn("Skipped (no images found): {$productName}");
                    //DB::rollBack();
                    continue;
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

                $productImages = [];

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
                }

                ProductImage::insert($productImages);

                //DB::commit();
                $this->info("Product added: {$productModel->name}");

            } catch (\Exception $e) {
                //DB::rollBack();
                Log::error("Product skipped: {$productName} - " . $e->getMessage());
                $this->error("Skipped category: {$categoryId} product: {$productName}");
            }
        }
    }

    private function getUnsplashImages(string $query, int $count = 5, int $width = 600, int $height = 400): array
    {
        $accessKey = config('custom.unsplash_access_key');

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
