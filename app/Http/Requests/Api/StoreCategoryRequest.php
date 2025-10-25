<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn($query) =>
                    $query->where('parent_id', $this->parent_id)
                ),
            ],
            'description' => 'nullable|string',
            'image'       => 'nullable|string',
            'parent_id'   => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.unique'      => 'This category name already exists under the selected parent.',
            'parent_id.exists' => 'The selected parent category does not exist.',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Custom JSON response for API
        $response = response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors'  => $validator->errors(),
        ], 422);

        throw new \Illuminate\Http\Exceptions\HttpResponseException($response);
    }
}
