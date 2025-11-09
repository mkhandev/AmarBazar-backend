<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCategoryRequest;
use App\Http\Requests\Api\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $imagePath = config('custom.image_path');

        $categories = Category::all();
        return response()->json($categories);
    }

    public function show($id)
    {
        $category = Category::with('children')->findOrFail($id);

        $response = [
            'success' => true,
            'message' => "Category data",
            'data'    => $category,
        ];
        return response()->json($response, 200);
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        $response = [
            'success' => true,
            'message' => "Category created successfully",
            'data'    => $category,
        ];

        return response()->json($response, 200);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated         = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return response()->json($category);

    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
