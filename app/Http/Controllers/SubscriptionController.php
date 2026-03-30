<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        // Protect all routes except for index and show
        $this->middleware('auth:sanctum')->except(['index', 'show','allsubscriptions']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $subscriptions = Subscription::orderBy('subscription_id', 'desc')->get();
            return response()->json(['data' => $subscriptions], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching subscriptions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch subscriptions'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'total_viewers' => 'required', // Changed: removed integer validation
            'logo_img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('logo_img_file')) {
                $image = $request->file('logo_img_file');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $destinationPath = public_path('uploads/subscription_logos');
                File::makeDirectory($destinationPath, 0755, true, true);
                $image->move($destinationPath, $imageName);
                $data['logo_img_file'] = 'uploads/subscription_logos/' . $imageName;
            }

            $subscription = Subscription::create($data);
            return response()->json(['message' => 'Subscription created successfully', 'data' => $subscription], 201);
        } catch (\Exception $e) {
            Log::error('Error creating subscription: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create subscription'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($subscription_id): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($subscription_id);
            return response()->json(['data' => $subscription], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Subscription not found'], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching subscription {$subscription_id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch subscription'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $subscription_id): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($subscription_id);

            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:255',
                'total_viewers' => 'required', // Changed: removed integer validation
                'logo_img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('logo_img_file')) {
                // Delete old image if it exists
                if ($subscription->logo_img_file && File::exists(public_path($subscription->logo_img_file))) {
                    File::delete(public_path($subscription->logo_img_file));
                }

                $image = $request->file('logo_img_file');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $destinationPath = public_path('uploads/subscription_logos');
                $image->move($destinationPath, $imageName);
                $data['logo_img_file'] = 'uploads/subscription_logos/' . $imageName;
            }

            $subscription->update($data);

            return response()->json(['message' => 'Subscription updated successfully', 'data' => $subscription->fresh()], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Subscription not found'], 404);
        } catch (\Exception $e) {
            Log::error("Error updating subscription {$subscription_id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update subscription'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($subscription_id): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($subscription_id);

            // Delete the image file from storage
            if ($subscription->logo_img_file && File::exists(public_path($subscription->logo_img_file))) {
                File::delete(public_path($subscription->logo_img_file));
            }

            $subscription->delete();

            return response()->json(['message' => 'Subscription deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Subscription not found'], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting subscription {$subscription_id}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to delete subscription'], 500);
        }
    }

    /**
     * Fetch all subscription records without pagination.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function allsubscriptions(): JsonResponse
    {
        try {
            // Fetch all subscriptions, ordering by the category name alphabetically
            $subscriptions = Subscription::orderBy('category', 'asc')->get();
            
            return response()->json(['data' => $subscriptions], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching all subscriptions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve subscriptions'], 500);
        }
    }
}