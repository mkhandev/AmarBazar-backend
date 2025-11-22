<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category:id,name', 'user:id,name', 'images', 'reviews']);
        //$query = Product::query();

        if ($request->filled('category') && $request->category != 'all') {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('q') && $request->q != 'all') {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('price') && $request->price != 'all') {
            $priceRange = explode('-', $request->price);

            if (count($priceRange) === 2) {
                $min = (float) $priceRange[0];
                $max = (float) $priceRange[1];
                $query->whereBetween('price', [$min, $max]);
            } elseif (count($priceRange) === 1) {
                $min = (float) rtrim($priceRange[0], '+');
                $query->where('price', '>=', $min);
            }
        }

        if ($request->filled('rating') && $request->rating !== 'all') {
            // $query->whereHas('reviews', function ($q) use ($request) {
            //     $q->selectRaw('avg(rating) as avg_rating')
            //         ->groupBy('product_id');
            // });

            // simpler version if `reviews` relation has `rating` field:
            // $query->whereHas('reviews', function ($q) use ($request) {
            //     $q->where('rating', '>=', (int) $request->rating);
            // });

            $query->where('rating', '>=', (float) $request->rating);
        }

        if ($request->filled('sortby')) {
            switch ($request->sortby) {
                case 'price-low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price-high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating-low':
                    $query->orderBy('rating', 'asc');
                    break;
                case 'rating-high':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'latest':
                    $query->orderBy('id', 'desc');
                    break;
                default:
                    $query->orderBy('id', 'desc');
                    break;
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        if ($request->filled('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $products = $query->paginate(15);

        return response()->json($products);
    }

    public function show($idOrSlug): JsonResponse
    {
        $product = Product::with(['category', 'user', 'images', 'reviews', 'reviews.user'])
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $response = [
            'success' => true,
            'message' => "Product details",
            'data'    => $product,
        ];

        return response()->json($response);
    }

    public function destroy(Product $product): JsonResponse
    {
        DB::beginTransaction();

        try {

            // Delete all product images from storage
            foreach ($product->images as $image) {
                $url      = $image->image;
                $path     = parse_url($url, PHP_URL_PATH);
                $filePath = str_replace('/storage/', '', $path);

                Storage::disk('public')->delete($filePath);
                $image->delete();
            }

            // Delete the product
            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function authCheck()
    {
        $response = [
            'success' => true,
            'message' => "Successfully you can access",
        ];

        return response()->json($response);
    }

    public function productDetails($id): JsonResponse
    {
        $product = Product::with(['category:id,name', 'images:id,product_id,image,is_main'])
            ->where('id', $id)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $response = [
            'success' => true,
            'message' => "Product details",
            'data'    => $product,
        ];

        return response()->json($response);
    }

    public function addProduct(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|min:3',
            'category_id'      => 'required|integer|exists:categories,id',
            'brand'            => 'required|string|min:3',
            'price'            => 'required|numeric|min:0.01',
            'stock'            => 'required|integer|min:0',
            'description'      => 'required|string|min:3',
            'status'           => 'required|integer|in:0,1',
            'images.*'         => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'main_image_index' => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {

            $user = Auth::user();

            $product = Product::create([
                'name'        => $request->name,
                'category_id' => $request->category_id,
                'brand'       => $request->brand,
                'price'       => $request->price,
                'stock'       => $request->stock,
                'description' => $request->description,
                'status'      => $request->status,
                'user_id'     => $user->id,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path     = $image->store('products', 'public');
                    $imageUrl = Storage::url($path);

                    $product->images()->create([
                        'image'   => $imageUrl,
                        'is_main' => $index == $request->main_image_index,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'product' => $product->load('images'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Undo any DB changes if something fails
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'name'               => 'required|string|min:3',
            'category_id'        => 'required|integer|exists:categories,id',
            'brand'              => 'required|string|min:3',
            'price'              => 'required|numeric|min:0.01',
            'stock'              => 'required|integer|min:0',
            'description'        => 'required|string|min:3',
            'status'             => 'required|integer|in:0,1',

            // new images
            'images.*'           => 'image|mimes:jpeg,png,jpg,gif|max:2048',

            // client will send only IDs to delete
            'remove_image_ids'   => 'array',
            'remove_image_ids.*' => 'integer|exists:product_images,id',

            // id of main image OR index for newly uploaded images
            'main_image_index'   => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {

            $product = Product::with('images')->findOrFail($id);

            // Update product basic fields
            $product->update([
                'name'        => $request->name,
                'category_id' => $request->category_id,
                'brand'       => $request->brand,
                'price'       => $request->price,
                'stock'       => $request->stock,
                'description' => $request->description,
                'status'      => $request->status,
            ]);

            /** -------------------------------------------
             *  REMOVE SELECTED IMAGES
             * --------------------------------------------*/
            if ($request->remove_image_ids) {
                foreach ($request->remove_image_ids as $imgId) {
                    $image = $product->images->where('id', $imgId)->first();
                    if ($image) {
                        // delete file
                        $filePath = str_replace('/storage/', '', $image->image);
                        Storage::disk('public')->delete($filePath);

                        $image->delete();
                    }
                }
            }

            /** -------------------------------------------
             *  ADD NEW IMAGES
             * --------------------------------------------*/
            $newImageIds = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $imageFile) {
                    $path     = $imageFile->store('products', 'public');
                    $imageUrl = Storage::url($path);

                    $img = $product->images()->create([
                        'image'   => $imageUrl,
                        'is_main' => false,
                    ]);

                    $newImageIds[$index] = $img->id;
                }
            }

            /** -------------------------------------------
             *  UPDATE MAIN IMAGE
             * --------------------------------------------*/
            if ($request->filled('main_image_index')) {
                // remove old main images
                $product->images()->update(['is_main' => false]);

                $mainIndex = (int) $request->main_image_index;

                $existingImages = $product->images()->pluck('id')->toArray();
                $totalExisting  = count($existingImages);

                if ($mainIndex < $totalExisting) {
                    // existing image
                    $product->images()->where('id', $existingImages[$mainIndex])->update(['is_main' => true]);
                } else {
                    // new image
                    $newIndex = $mainIndex - $totalExisting;
                    if (isset($newImageIds[$newIndex])) {
                        $product->images()->where('id', $newImageIds[$newIndex])->update(['is_main' => true]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                "message" => "Successfully update product",
                'data'    => $product->load('images'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
