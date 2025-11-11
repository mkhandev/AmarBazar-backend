<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderController extends Controller
{
    /**
     * List authenticated user's orders
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cart_id'         => 'required|integer|exists:carts,id',
            'session_cart_id' => 'required|string',
            'user_id'         => 'required|integer|exists:users,id',
        ]);

        $cart = Cart::with('items.product')
            ->where('id', $request->cart_id)
            ->where('user_id', $request->user_id)
            ->where('session_cart_id', $request->session_cart_id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty or invalid'], 400);
        }

        $userInfo = User::where('id', $request->user_id)->first();

        DB::beginTransaction();
        try {
            // Calculate totals
            $total_amount = $cart->items->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            $shippingFee = config('custom.shipping_fee', 50);
            $tax         = config('custom.tax', 0);

            $grandTotal = $total_amount + $shippingFee + $tax;

            $order = Order::create([
                'user_id'              => $request->user_id,
                'order_number'         => Order::generateOrderNumber(),
                'total_amount'         => $total_amount,
                'shipping_fee'         => $shippingFee,
                'tax_amount'           => $tax,
                'grand_total'          => $grandTotal,
                'status'               => 'pending',

                'shipping_name'        => $userInfo->name,
                'shipping_phone'       => $userInfo->phone,
                'shipping_address'     => $userInfo->address,
                'shipping_city'        => $userInfo->city,
                'shipping_postal_code' => $userInfo->postal_code,
                'shipping_country'     => $userInfo->country,
                'payment_method'       => $userInfo->payment_method,

                'payment_status'       => 'pending',

            ]);

            // Create order items
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_price' => $item->product->price,
                    'sub_total'  => $item->product->price * $item->product->price,
                ]);
            }

            // Clear cart (optional)
            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data'    => $order,

            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $order_id)
    {
        $order = Order::where('id', $order_id)
            ->orWhere('order_number', $order_id)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Only owner can view
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $order->load('items.product.images'),
        ]);
    }

    public function checkToken(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json([
                'valid' => true,
                'user'  => $user,
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json(['valid' => false, 'message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['valid' => false, 'message' => 'Token invalid'], 401);
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => 'Token not provided'], 401);
        }
    }
}
