<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function submitRating(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pharmacist_id' => 'required|exists:pharmacists,id',
            'stars'         => 'required|integer|min:1|max:5',
        ]);

        $alreadyRated = Rating::where('pharmacist_id', $request->pharmacist_id)
            ->exists();

        if ($alreadyRated) {
            return response()->json([
                'message' => 'You have already rated the app',
            ], 400);
        }

        $rating = Rating::create([
            'pharmacist_id' => $request->pharmacist_id,
            'stars'         => $request->stars,
            'date'          => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Rating submitted successfully',
            'rating'  => $rating,
        ], 201);
    }
}
