<?php

namespace App\Http\Controllers;

use App\Models\Pharmacist;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PharmacistController extends Controller
{

    public function registerPharmacist(Request $request): \Illuminate\Http\JsonResponse
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
            'message'        => 'Pharmacist registered successfully',
            'pharmacist_id'  => $pharmacist->id,
        ], 201);
    }

    public function registerPharmacy(Request $request): \Illuminate\Http\JsonResponse
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

        /** @noinspection PhpUndefinedFieldInspection */
        $pharmacy = Pharmacy::create([
            'pharmacist_id'    => $request->pharmacist_id,
          'pharmacy_name'    => $request->pharmacy_name,
            'pharmacy_address' => $request->pharmacy_address,
          'certificate'      => json_encode([$certificate]),
           'license'          => json_encode([$license]),
        ]);

        return response()->json([
            'message'  => 'Pharmacy registered successfully',
            'pharmacy' => $pharmacy,

        ], 201);
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $pharmacist = Pharmacist::where('email', $request->email)->first();

        if (!$pharmacist || !Hash::check($request->password, $pharmacist->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }


        $pharmacy = $pharmacist->pharmacy;

        if ($pharmacy->status !== 'approved') {
            return response()->json([
                'message' => 'Your account is pending admin approval',
            ], 403);
        }

        $token = $pharmacist->createToken('pharmacist-token')->plainTextToken;

        return response()->json([
            'message'    => 'Login successful',
            'token'      => $token,
            'pharmacist' => $pharmacist,
        ]);

        }
}
