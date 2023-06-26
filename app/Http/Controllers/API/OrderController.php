<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    // Get Menu Categories
    public function getMenuCategories()
    {
        $categories = MenuCategory::all();
        return response()->json($categories);
    }

    // Get Menu Category Items
    public function getMenuCategoryItems($categoryId)
    {
        $items = MenuItem::where('category_id', $categoryId)->get();
        return response()->json($items);
    }

    // Create Order
    public function createOrder(Request $request)
    {
        // Validate request data
        $request->validate([
            'delivery_address' => 'required',
            'delivery_latitude' => 'required',
            'delivery_longitude' => 'required',
            'coupon_code' => 'nullable|exists:coupons,code',
            'payment_method' => 'required|in:card,mtn_momo,airtel_money',
            'menu_items' => 'required|array',
            'menu_items.*.item_id' => 'required|exists:menu_items,id',
            'menu_items.*.quantity' => 'required|integer|min:1',
            'menu_items.*.addons' => 'nullable|array',
            'menu_items.*.addons.*.item_id' => 'required|exists:menu_items,id',
            'menu_items.*.addons.*.quantity' => 'required|integer|min:1',
        ]);

        // Retrieve the authenticated user
        $user = $request->user();

        // Create a new order instance
        $order = new Order();
        $order->user_id = $user->id;
        $order->delivery_address = $request->input('delivery_address');
        $order->delivery_latitude = $request->input('delivery_latitude');
        $order->delivery_longitude = $request->input('delivery_longitude');
        $order->coupon_code = $request->input('coupon_code');
        $order->payment_method = $request->input('payment_method');
        // Set other order properties as needed

        // Calculate the total amount for the order
        $orderTotal = 0;

        // Process menu items
        foreach ($request->input('menu_items') as $menuItemData) {
            $menuItem = MenuItem::find($menuItemData['item_id']);
            $quantity = $menuItemData['quantity'];

            // Calculate the subtotal for the menu item
            $menuItemSubtotal = $menuItem->price * $quantity;

            // Add the subtotal to the order total
            $orderTotal += $menuItemSubtotal;

            // Process addons
            if (isset($menuItemData['addons'])) {
                foreach ($menuItemData['addons'] as $addonData) {
                    $addonItem = MenuItem::find($addonData['item_id']);
                    $addonQuantity = $addonData['quantity'];

                    // Calculate the subtotal for the addon
                    $addonSubtotal = $addonItem->price * $addonQuantity;

                    // Add the subtotal to the order total
                    $orderTotal += $addonSubtotal;
                }
            }
        }

        // Apply the coupon discount if a valid coupon is provided
        $couponCode = $request->input('coupon_code');
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();

            if ($coupon) {
                // Check the coupon type and apply the discount accordingly
                if ($coupon->type === 'fixed') {
                    $orderTotal -= $coupon->value;
                } elseif ($coupon->type === 'percentage') {
                    $discountAmount = ($coupon->value / 100) * $orderTotal;
                    $orderTotal -= $discountAmount;
                }
            }
        }

        // Save the order total to the order instance
        $order->total_amount = $orderTotal;

        // Save the order to the database
        $order->save();

        // Initiate payment with Flutterwave
        $paymentMethod = $request->input('payment_method');

        if ($paymentMethod === 'airtel_money') {
            $network = 'AIRTEL';
        } elseif ($paymentMethod === 'mtn_momo') {
            $network = 'MTN';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('FLW_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.flutterwave.com/v3/charges?type=mobile_money_uganda', [
            'phone_number' => $user->phone_number,
            'network' => $network,
            'amount' => $order->total_amount,
            'currency' => 'UGX',
            'email' => $user->email,
            'tx_ref' => 'order-' . $order->id,
            'customer' => [
                'id' => $user->id,
                'order_id' => $order->id,
            ]
        ]);

        // Check if the request was successful
        if ($response->successful()) {
            $data = $response->json(); // Get the response data as JSON

            // Check if the authorization mode is "redirect"
            if ($data['meta']['authorization']['mode'] === 'redirect') {
                $paymentLink = $data['meta']['authorization']['redirect'];

                // Return a JSON response with the payment link
                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Payment initiated',
                    'payment_link' => $paymentLink
                ]);
            }

            return response()->json([
                'status' => 'FAILED',
                'message' => 'Unable to complete payment at the moment'
            ]);
        } else {
            // Request failed, handle the error
            $errorMessage = $response->json()['message'];

            // Return an error response to the client or redirect to an error page
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate transaction: ' . $errorMessage,
            ]);
        }
    }


    // Get Orders
    public function getOrders()
    {
        $orders = Order::with(['user', 'menuItems', 'menuItems.addons', 'payment'])->get();
        return response()->json($orders);
    }

    // Get Order By ID
    public function getOrderById($orderId)
    {
        $order = Order::with(['user', 'menuItems', 'menuItems.addons', 'payment'])->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    // Update Order
    public function updateOrder(Request $request, $orderId)
    {
        // Validate request data

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update order logic

        return response()->json(['message' => 'Order updated successfully']);
    }

    // Delete Order
    public function deleteOrder($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Delete order logic

        return response()->json(['message' => 'Order deleted successfully']);
    }

    // Get Pending Orders
    public function getPendingOrders()
    {
        $pendingOrders = Order::where('status', 'pending')->get();
        return response()->json($pendingOrders);
    }
}
