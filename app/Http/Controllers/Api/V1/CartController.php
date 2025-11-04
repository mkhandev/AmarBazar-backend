<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $query = Cart::with('product');

        if (auth('api')->check()) {
            $user = auth('api')->user();

            $query->where('user_id', $user->id());
        } elseif ($request->session_cart_id) {
            $query->where('session_cart_id', $request->session_cart_id);
        } else {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartItems = $query->get();

        return response()->json([
            'success' => true,
            'message' => "Cart details",
            'data'    => $cartItems,
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id'      => 'required|exists:products,id',
            'quantity'        => 'required|integer|min:1',
            'session_cart_id' => 'required|string',
            'shipping_price'  => 'nullable|numeric|min:0',
            'tax_price'       => 'nullable|numeric|min:0',
        ]);

        $product  = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;

        // Default calculations — you can change this logic
        $item_price     = $product->price;
        $shipping_price = $request->shipping_price ?? 50.00;
        $tax_price      = $request->tax_price ?? ($item_price * $quantity * 0.1); //10% tax

        $cartData = [
            'session_cart_id' => $request->session_cart_id,
            'product_id'      => $product->id,
            'quantity'        => $quantity,
            'item_price'      => $item_price,
            'shipping_price'  => $shipping_price,
            'tax_price'       => $tax_price,
            'total_price'     => ($item_price * $quantity) + $shipping_price + $tax_price,
        ];

        if (auth('api')->check()) {
            $user                = auth('api')->user();
            $cartData['user_id'] = $user->id;

            $existing = Cart::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();
        } else {
            $existing = Cart::where('session_cart_id', $request->session_cart_id)
                ->where('product_id', $product->id)
                ->first();
        }

        if ($existing) {

            $existing->quantity += $quantity;
            $existing->tax_price      = $tax_price;
            $existing->shipping_price = $shipping_price;
            $existing->total_price    = ($existing->item_price * $quantity)
             + $existing->shipping_price
             + $existing->tax_price;
            $existing->save();

            return response()->json(['message' => 'Cart updated', 'cart' => $existing]);
        }

        $cart = Cart::create($cartData);

        return response()->json(['message' => 'Item added to cart', 'cart' => $cart]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity'       => 'required|integer|min:1',
            'shipping_price' => 'nullable|numeric|min:0',
            'tax_price'      => 'nullable|numeric|min:0',
        ]);

        $cart = Cart::findOrFail($id);

        $cart->quantity       = $request->quantity;
        $cart->shipping_price = $request->shipping_price ?? $cart->shipping_price;
        $cart->tax_price      = $request->tax_price ?? $cart->tax_price;
        $cart->total_price    = ($cart->item_price * $cart->quantity)
         + $cart->shipping_price
         + $cart->tax_price;
        $cart->save();

        return response()->json(['message' => 'Cart updated', 'cart' => $cart]);
    }

    public function destroy($id)
    {
        $cart = Cart::findOrFail($id);
        $cart->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    public function updateByProduct(Request $request)
    {
        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
            'shipping_price' => 'nullable|numeric|min:0',
            'tax_price'      => 'nullable|numeric|min:0',

        ]);

        $userId    = $request->user()?->id;
        $sessionId = $request->session_cart_id;

        // Fetch the product price from the database
        $product   = Product::findOrFail($request->product_id);
        $itemPrice = $product->price;

        // Find cart item
        $cartItem = Cart::where('product_id', $request->product_id)
            ->when($userId, fn($query) => $query->where('user_id', $userId))
            ->when(! $userId && $sessionId, fn($query) => $query->where('session_cart_id', $sessionId))
            ->firstOrFail();

        // Update price fields
        $shipping_price = $request->shipping_price ?? $cartItem->shipping_price ?? 50.00;
        $tax_price      = $request->tax_price ?? ($product->price * $request->quantity * 0.1); //10% tax

        $cartItem->item_price     = $product->price;
        $cartItem->shipping_price = $shipping_price;
        $cartItem->tax_price      = $tax_price;

        // Update quantity if sent, else keep existing
        $cartItem->quantity += $request->quantity;

        // Recalculate total price
        $cartItem->total_price += ($product->price * $request->quantity)
             + $shipping_price
             + $tax_price;

        $cartItem->save();

        return response()->json($cartItem);
    }

}
