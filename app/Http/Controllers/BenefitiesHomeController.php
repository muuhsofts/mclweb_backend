<?php

namespace App\Http\Controllers;

use App\Models\BenefitiesHome;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class BenefitiesHomeController extends Controller
{
    /**
     * The path for storing uploaded images.
     */
    private const IMAGE_UPLOAD_PATH = 'uploads/benefities_homes';

    public function __construct()
    {
        // Protect all methods except the public-facing ones.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'benefitiesHomeSlider']);
    }

    /**
     * Display a listing of benefities home sliders for admin view.
     */
    public function index()
    {
        // Return the collection directly. Laravel automatically converts it to JSON.
        return BenefitiesHome::orderBy('benefit_home_id', 'desc')->get();
    }

    /**
     * Display benefities home sliders for public view.
     */
    public function benefitiesHomeSlider()
    {
        // Return the collection directly. This endpoint is public via middleware.
        return BenefitiesHome::orderBy('benefit_home_id', 'desc')->get();
    }

    /**
     * Store a newly created benefities home slider.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Specific image validation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['home_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            }

            $slider = BenefitiesHome::create($validatedData);

            return response()->json([
                'message' => 'Benefities home slider created successfully.',
                'slider' => $slider
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating benefities home slider: ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified benefities home slider.
     */
    public function show($benefit_home_id)
    {
        // findOrFail automatically handles the 'not found' case by throwing an exception,
        // which Laravel's handler converts to a 404 response.
        $slider = BenefitiesHome::findOrFail($benefit_home_id);

        // Return the model directly.
        return $slider;
    }

    /**
     * Update the specified benefities home slider.
     */
    public function update(Request $request, $benefit_home_id)
    {
        // findOrFail ensures the slider exists before proceeding.
        $slider = BenefitiesHome::findOrFail($benefit_home_id);

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // `nullable` allows null or a file
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        try {
            $validatedData = $validator->validated();

            // Handle the file update logic cleanly
            if ($request->hasFile('home_img')) {
                // 1. New file uploaded: Delete old and store new
                if ($slider->home_img) {
                    File::delete(public_path($slider->home_img));
                }
                $image = $request->file('home_img');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['home_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                // 2. Explicitly removing the file by sending `null`
                if ($slider->home_img) {
                    File::delete(public_path($slider->home_img));
                }
                $validatedData['home_img'] = null;
            }
            // 3. No file change: The 'home_img' key is not in validatedData, so it won't be updated.

            $slider->update($validatedData);

            return response()->json([
                'message' => 'Benefities home slider updated successfully.',
                'slider' => $slider->fresh() // Return the updated model
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating benefities home slider ID {$benefit_home_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred during the update.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified benefities home slider from storage.
     */
    public function destroy($benefit_home_id)
    {
        // findOrFail ensures the slider exists before attempting to delete.
        $slider = BenefitiesHome::findOrFail($benefit_home_id);

        try {
            // Delete the associated image file if it exists.
            if ($slider->home_img) {
                File::delete(public_path($slider->home_img));
            }

            $slider->delete();

            // Return a 204 No Content response, which is the standard for a successful deletion.
            return response()->noContent();
            
        } catch (Exception $e) {
            Log::error("Error deleting benefities home slider ID {$benefit_home_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred during deletion.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}