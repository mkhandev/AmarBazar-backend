<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with(['category', 'user', 'images', 'reviews'])
            ->orderBy('id', 'desc')
            ->paginate(14);

        return response()->json($products);
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
