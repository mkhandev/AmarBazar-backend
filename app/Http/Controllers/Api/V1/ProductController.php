<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'user', 'images', 'reviews']);
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

        $products = $query->paginate(15);
        //$products = $query->take(5)->get();

        return response()->json($products);
    }

    public function show($id): JsonResponse
    {
        // Fetch product with related data
        $product = Product::with(['category', 'user', 'images', 'reviews'])->find($id);

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

    public function store(StoreProductRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $product = Product::create($request->validated());

            // handle multiple images
            if ($request->filled('images')) {
                foreach ($request->images as $img) {
                    $product->images()->create([
                        'image'   => $img['image'],
                        'is_main' => $img['is_main'] ?? false,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'data'    => $product->load('images'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        DB::beginTransaction();

        try {
            $product->update($request->validated());

            if ($request->filled('images')) {
                // delete old images and replace
                $product->images()->delete();

                foreach ($request->images as $img) {
                    $product->images()->create([
                        'image'   => $img['image'],
                        'is_main' => $img['is_main'] ?? false,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'data'    => $product->load('images'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

}
