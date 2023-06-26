<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;


class AccountController extends Controller
{
    public function getProfile(Request $request)
    {
        // Find the user
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Account does not exist',
            ], 404);
        }

        // Return the user's profile details
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'User profile',
            'user' => [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'phone_number' => 'required'
        ]);

        // Find the user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Account does not exist',
            ], 404);
        }

        // Update the user's profile
        if ($request->firstname) {
            $user->firstname = $request->firstname;
        }
        if ($request->lastname) {
            $user->lastname = $request->lastname;
        }
        if ($request->phone_number) {
            $user->phone_number = $request->phone_number;
        }

        $user->save();

        // Return the updated user details
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Profile updated',
            'user' => [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
            ]
        ]);
    }

    public function updateFCMToken(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        // Find the user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Account does not exist',
            ], 404);
        }

        // Update the FCM token
        $user->fcm_token = $request->fcm_token;
        $user->save();

        // Return the updated user details
        return response()->json([
            'fcm_token' => $user->fcm_token,
        ]);
    }

    public function deleteAccount(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'pincode' => 'required'
        ]);

        // Find the user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Account does not exist',
            ], 404);
        }

        // Delete the user
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
