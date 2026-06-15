<?php

namespace App\Http\Controllers;

use App\Models\Pharmacist;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PharmacistController extends Controller
{

    public function registerPharmacist(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:pharmacists,email',
            'password' => 'required|min:6',
            'profile'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $profile = null;
        if ($request->hasFile('profile')) {
            $profile = $request->file('profile')->store('profiles', 'public');
        }

        $pharmacist = Pharmacist::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'profile'  => $profile,
        ]);

        return response()->json([
         //   'status'  => true,
            'message' => 'Pharmacist registered successfully',
            'data'    => $pharmacist,
        ], 201);
    }


    public function registerPharmacy(Request $request)
    {
        $request->validate([
            'pharmacist_id'    => 'required|exists:pharmacists,id',
            'pharmacy_name'    => 'required|string',
            'pharmacy_address' => 'required|string',
            'certificate'      => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
            'license'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $certificate = $request->file('certificate')->store('certificates', 'public');
        $license     = $request->file('license')->store('licenses', 'public');

        $pharmacy = Pharmacy::create([
            'pharmacist_id'    => $request->pharmacist_id,
            'pharmacy_name'    => $request->pharmacy_name,
            'pharmacy_address' => $request->pharmacy_address,
            'certificate'      => json_encode([$certificate]),
            'license'          => json_encode([$license]),
            'status'           => 'pending',
        ]);

        return response()->json([
            //'status'  => true,
            'message' => 'Pharmacy registered successfully, waiting for admin approval',
            'data'    => $pharmacy,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $pharmacist = Pharmacist::where('email', $request->email)->first();

        if (!$pharmacist || !Hash::check($request->password, $pharmacist->password)) {
            return response()->json([
             //   'status'  => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $pharmacy = $pharmacist->pharmacy;


        if (!$pharmacy) {
            return response()->json([
              //  'status'  => false,
                'message' => 'You must register a pharmacy first',
            ], 403);
        }

        if ($pharmacy->status === 'pending') {
            return response()->json([
               // 'status'  => false,
                'message' => 'Your pharmacy is pending admin approval',
            ], 403);
        }

        if ($pharmacy->status === 'rejected') {
            return response()->json([
             //   'status'  => false,
                'message' => 'Your pharmacy has been rejected',
            ], 403);
        }

        $token = $pharmacist->createToken('pharmacist-token')->plainTextToken;

        return response()->json([
           // 'status'  => true,
            'message' => 'Login successful',
            'token'   => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
          //  'status'  => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $pharmacist = $request->user();

        $pharmacist->tokens()->delete();
        $pharmacist->delete();

        return response()->json([
         //   'status'  => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    public function getProfile(Request $request)
    {
        $pharmacist = $request->user();

        return response()->json([
           // 'status' => true,
            'data'   => [
                'pharmacist' => $pharmacist,
                'pharmacy'   => $pharmacist->pharmacy
            ]
        ]);
    }

    public function approvePharmacy($id)
    {
        $pharmacy = Pharmacy::findOrFail($id);
        $pharmacy->status = 'approved';
        $pharmacy->save();

        return response()->json([
        //    'status'  => true,
            'message' => 'Pharmacy approved successfully'
        ]);
    }
    public function rejectPharmacy($id)
    {
        $pharmacy = Pharmacy::findOrFail($id);
        $pharmacy->status = 'rejected';
        $pharmacy->save();

        return response()->json([
           // 'status'  => true,
            'message' => 'Pharmacy rejected'
        ]);
    }
}
