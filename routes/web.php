<?php

use App\Events\PaymentCompleteEvent;
use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::post('/payment_callback', function (Request $request) {
    $callbackData = $request->all();

    $status = $callbackData['status'];
    $orderId = str_replace('order-', '', $callbackData['txRef']);

    if ($status === 'successful') {
        $payment = Payment::where('order_id', $orderId)->first();
        if ($payment) {
            $payment->status = 'completed';
            $payment->updated_at = now();
            $payment->save();

            event(new PaymentCompleteEvent(
                'SUCCESS',
                "Your payment of {$payment->amount} was successful",
                $payment->customer_id // Pass the user ID to the event
            ));

            $notification = Notification::create([
                'title' => 'New Payment',
                'body' => 'You have a new payment of ' . $payment->amount,
                'type' => 'payment',
                'user_id' => $payment->customer_id,
                'created_at' => now(),
            ]);


            return response()->json([
                'status' => 'SUCCESS'
            ]);
        }

        return response()->json([
            'status' => 'Unable to find the payment information'
        ]);
    }
});
