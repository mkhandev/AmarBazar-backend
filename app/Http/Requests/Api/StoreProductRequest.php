<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:products,slug',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'stock'            => 'required|integer|min:0',
            'brand'            => 'nullable|string|max:255',
            'rating'           => 'nullable|numeric|min:0|max:5',
            'num_reviews'      => 'nullable|integer|min:0',
            'category_id'      => 'required|exists:categories,id',
            'user_id'          => 'required|exists:users,id',
            'status'           => 'boolean',

            // for multiple images
            'images'           => 'nullable|array',
            'images.*.image'   => 'required|string',
            'images.*.is_main' => 'boolean',
        ];
    }
}
