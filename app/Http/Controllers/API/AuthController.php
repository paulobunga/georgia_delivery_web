<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validationRegex = "#(^\+256[0-9]{9}$)|(^[0-9]{9}$)#";

        $validation = Validator::make($request->all(), [
            'phone_number' => [
                'required',
                'regex:' . $validationRegex,
            ],
            'pincode' => 'required|digits:4',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first(),
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $pincode = $request->pincode;

        $user = User::where(['phone_number' => $phoneNumber, 'pincode'  => $pincode])->first();

        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Invalid phone number or pincode'
            ], 401);
        }

        // Laravel Sanctum creates a token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Append token to user
        $user->token = $token;

        // Return the user's ID
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'You have been authenticated',
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone_number' => $user->phone_number,
                'token' => $token
            ]
        ]);
    }

    private function generateOTP()
    {
        return rand(100000, 999999);
    }

    private function sendNewPincode($phoneNumber, $otp): bool
    {
        try {
            $apiKey = "6E8jqkyPRIuYuP9-XJWw5g==";

            // Set the message to send, including the OTP
            $message = "Your Boda Boda Spa pincode has been changed. Your new pincode is " . $otp;

            // Set the Clickatell API URL and the parameters for the request
            $apiUrl = "https://platform.clickatell.com/messages/http/send";
            $queryParams = [
                "apiKey" => $apiKey,
                "to" => $phoneNumber,
                "content" => $message
            ];

            // Send the request to the Clickatell API
            $response = Http::get($apiUrl, $queryParams);

            // Check the status code of the response
            if ($response->successful()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }


    public function register(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'phone_number' => 'required',
            'pincode' => 'required|digits:4',
        ]);

        // Get current user from sanctum token
        $user = User::where('phone_number', $request->phone_number)->first();

        if ($user) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Sorry, the phone number is already registered.',
            ], 400);
        }

        $user = new User();

        // Update the user's profile
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->phone_number = $request->phone_number;
        $user->pincode = $request->pincode;

        $user->assignRole('customer');
        $user->save();

        // Authenticate user with id
        Auth::login($user);

        // Generate token for user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return the updated user details
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone_number' => $user->phone_number,
                'token' => $token
            ]
        ]);
    }

    public function resetPincode($request)
    {
        // Validate request
        $request->validate([
            'phone_number' => 'required'
        ]);

        // Get current user from sanctum token
        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Unable to find your account. Please check if number provided is correct'
            ]);
        }

        $user->pincode = $this->generateOTP();
        $user->save();

        // Send new pincode to user phone
        $this->sendNewPincode($user->phone_number, $user->pincode);

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Pincode changed. Please check registered phone number.'
        ]);
    }
}
