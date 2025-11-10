<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{

    public function show(Request $request, $session_cart_id)
    {
        $cart = Cart::with(['items.product.images'])->where('session_cart_id', $session_cart_id)->first();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found.',
            ], 404);
        }

        if (auth('api')->check()) {
            $user          = auth('api')->user();
            $cart->user_id = $user->id;
            $cart->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully.',
            'data'    => $cart,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id'      => 'required|exists:products,id',
            'quantity'        => 'required|integer|min:1|max:5',
            'session_cart_id' => 'required|string',
        ]);

        $cart = $this->getCart($request);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($item) {

            if (($item->quantity + $request->quantity) > 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only add up to 5 units of this product.',
                ], 422);
            }

            $item->update([
                'quantity' => $item->quantity + $request->quantity,
            ]);

        } else {
            $item = CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $request->product_id,
                'quantity'   => $request->quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'data'    => $item->load('product'),
        ]);
    }

    private function getCart(Request $request)
    {
        $cart = Cart::where('session_cart_id', $request->session_cart_id)->first();

        if ($cart && $request->filled('user_id') && ! $cart->user_id) {
            $cart->user_id = $request->user_id;
            $cart->save();
        }

        if (! $cart) {
            $cart = Cart::create([
                'session_cart_id' => $request->session_cart_id,
                'user_id'         => $request->user_id ?? null,
            ]);
        }

        return $cart;
    }

    public function update(Request $request, $session_cart_id, $item_id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:5',
            'user_id'  => 'nullable|exists:users,id',
        ]);

        $cart = Cart::where('session_cart_id', $session_cart_id)->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found.',
            ], 404);
        }

        if ($request->filled('user_id') && ! $cart->user_id) {
            $cart->user_id = $request->user_id;
            $cart->save();
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('id', $request->item_id)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.',
            ], 404);
        }

        if (($request->quantity) > 5) {
            return response()->json([
                'success' => false,
                'message' => 'You can only add up to 5 units of this product.',
            ], 422);
        }

        $item->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully.',
            'data'    => $item->load('product'),
        ]);
    }

    public function destroy($session_cart_id, $item_id)
    {
        // 1. Find the cart
        $cart = Cart::where('session_cart_id', $session_cart_id)->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found.',
            ], 404);
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('id', $item_id)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item deleted successfully.',
        ]);
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
            'payment_method' => 'required|in:cod,stripe',
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

    //Login time user_id update
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
}
