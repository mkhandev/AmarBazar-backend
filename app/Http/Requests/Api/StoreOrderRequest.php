<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
        return [
            'address'            => 'required|string',
            'city'               => 'nullable|string',
            'postal_code'        => 'nullable|string',
            'country'            => 'nullable|string',

            'payment_method'     => 'required|in:CashOnDelivery,Stripe',

            'shipping_price'     => 'nullable|numeric',
            'tax_price'          => 'nullable|numeric',
            'item_price'         => 'required|numeric',
            'total_price'        => 'required|numeric',

            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
        ];
    }
}
