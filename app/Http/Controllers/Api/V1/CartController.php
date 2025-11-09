<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
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

    public function show($session_cart_id, $user_id = null)
    {
        $query = Cart::where('session_cart_id', $session_cart_id)
            ->with(['product', 'product.images']);

        if ($user_id) {
            $query->where('user_id', $user_id);
        }

        $cart = $query->get();

        if ($cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart details',
            'data'    => $cart,
        ], 200);
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

        if ($quantity > 5) {
            return response()->json([
                'message' => 'You can only add up to 5 units of this product.',
            ], 422);
        }

        // Default calculations — you can change this logic
        $item_price     = $product->price;
        $shipping_price = $request->shipping_price ?? 50.00;
        $tax_price      = 0;
        //$tax_price      = $request->tax_price ?? ($item_price * $quantity * 0.1); //10% tax

        $cartData = [
            'session_cart_id' => $request->session_cart_id,
            'user_id'         => $request->user_id ?? null,
            'product_id'      => $product->id,
            'quantity'        => $quantity,
            'item_price'      => $item_price,
            'shipping_price'  => $shipping_price,
            'tax_price'       => $tax_price,
            'total_price'     => ($product->price * $quantity) + $shipping_price + $tax_price,
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

            $newQuantity = $quantity;

            if ($newQuantity > 5) {
                return response()->json([
                    'message' => 'You can only add up to 5 units of this product.',
                ], 422);
            }

            $existing->quantity       = $quantity;
            $existing->tax_price      = $tax_price;
            $existing->shipping_price = $shipping_price;
            $existing->total_price    = ($product->price * $quantity) + $shipping_price;
            $existing->save();

            return response()->json(['message' => 'Cart updated', 'cart' => $existing]);
        }

        $cart = Cart::create($cartData);

        return response()->json(['message' => 'Item added to cart', 'cart' => $cart]);
    }

    public function updateByProduct(Request $request)
    {
        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
            'shipping_price' => 'nullable|numeric|min:0',
            'tax_price'      => 'nullable|numeric|min:0',

        ]);

        $userId = "";
        if (auth('api')->check()) {
            $user   = auth('api')->user();
            $userId = $user->id;
        }
        //$userId    = $request->user()?->id;
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

        $cartItem->item_price     = $product->price;
        $cartItem->shipping_price = $shipping_price;

        // Update quantity if sent, else keep existing
        $newQuantity = $request->quantity;

        if ($newQuantity > 5) {
            return response()->json([
                'message' => 'You can only have up to 5 units of this product in your cart.',
            ], 422);
        }

        $cartItem->quantity = $newQuantity;

        // Recalculate total price
        $cartItem->total_price = ($product->price * $newQuantity) + $shipping_price;

        $cartItem->save();

        return response()->json($cartItem);
    }

    public function updateUser(Request $request)
    {
        $request->validate([
            'session_cart_id' => 'required|string',
            'user_id'         => 'required|integer|exists:users,id',
        ]);

        Cart::where('session_cart_id', $request->session_cart_id)
            ->update([
                'user_id' => $request->user_id,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated with user ID.',
        ]);
    }

    public function destroy($id, $session_cart_id)
    {
        $cart = Cart::where('id', $id)
            ->where('session_cart_id', $session_cart_id)
            ->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found or does not belong to this session.',
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
        ], 200);
    }

    public function updateShipping(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'required|string|max:500',
            'city'           => 'required|string|max:100',
            'postal_code'    => 'required|string|max:20',
            'country'        => 'required|string|max:100',
            'country'        => 'required|string|max:100',
            'payment_method' => 'required|in:CashOnDelivery,Stripe',
        ]);

        $cart = Cart::where('user_id', Auth::id())->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }

        $user = User::find(Auth::id());

        $user->name           = $request->name;
        $user->phone          = $request->phone;
        $user->address        = $request->address;
        $user->city           = $request->city;
        $user->postal_code    = $request->postal_code;
        $user->country        = $request->country;
        $user->payment_method = $request->payment_method;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Shipping info updated successfully',
            'data'    => $user,
        ]);
    }

}
