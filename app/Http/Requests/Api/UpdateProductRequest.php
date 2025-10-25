<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        $productId = $this->route('product')->id ?? null;

        return [
            'name'             => 'sometimes|required|string|max:255',
            'slug'             => 'sometimes|required|string|max:255|unique:products,slug,' . $productId,
            'description'      => 'nullable|string',
            'price'            => 'sometimes|required|numeric|min:0',
            'stock'            => 'sometimes|required|integer|min:0',
            'brand'            => 'nullable|string|max:255',
            'rating'           => 'nullable|numeric|min:0|max:5',
            'num_reviews'      => 'nullable|integer|min:0',
            'category_id'      => 'sometimes|required|exists:categories,id',
            'user_id'          => 'sometimes|required|exists:users,id',
            'status'           => 'boolean',

            'images'           => 'nullable|array',
            'images.*.image'   => 'required|string',
            'images.*.is_main' => 'boolean',
        ];
    }
}
