<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function placeOrder()
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->get();

        if ($cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty',
            ], 400);
        }

        //$userInfo = User::where('id', $user->id)->get();
        $shippingInfo = $user->only(['name', 'phone', 'address', 'city', 'postal_code', 'country', 'payment_method']);

        // echo "<pre>";
        // print_r($shippingInfo);
        // exit;

        $shippingFee = config('constants.shipping_fee', 50);
        $tax         = config('constants.tax', 0);

        $subtotal    = $cart->sum(fn($item) => $item->item_price * $item->quantity);
        $shippingFee = $shippingFee;
        $tax         = $tax;
        $total       = $subtotal + $shippingFee + $tax;

    }

    /**
     * Create an order
     */
    //public function store(StoreOrderRequest $request)
    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $request) {

            $order = Order::create([
                'user_id'        => $request->user()->id,
                'order_number'   => Order::generateOrderNumber(),
                'address'        => $data['address'],
                'city'           => $data['city'] ?? null,
                'postal_code'    => $data['postal_code'] ?? null,
                'country'        => $data['country'] ?? null,

                'payment_method' => $data['payment_method'],

                'shipping_price' => $data['shipping_price'] ?? 0,
                'tax_price'      => $data['tax_price'] ?? 0,
                'item_price'     => $data['item_price'],
                'total_price'    => $data['total_price'],
            ]);

            // Insert order items
            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data'    => $order->load('items'),
            ], 201);
        });
    }

    /**
     * Show single order
     */
    public function show(Request $request, Order $order)
    {
        // Only owner can view
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $order->load('items'),
        ]);
    }
}
