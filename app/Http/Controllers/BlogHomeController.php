<?php

namespace App\Http\Controllers;

use App\Models\BlogHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class BlogHomeController extends Controller
{
    /**
     * The path for storing uploaded images.
     */
    private const IMAGE_UPLOAD_PATH = 'uploads/blog_homes';

    public function __construct()
    {
        // Protect all methods except the public-facing ones.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'blogHomeSlider']);
    }

    /**
     * Display a listing of blog home sliders for admin view.
     */
    public function index()
    {
        // Return the collection directly. Laravel automatically converts it to JSON.
        return BlogHome::orderBy('blog_home_id', 'desc')->get();
    }

    /**
     * Display blog home sliders for public view.
     */
    public function blogHomeSlider()
    {
        // This endpoint is public via middleware.
        return BlogHome::orderBy('blog_home_id', 'desc')->get();
    }

    /**
     * Store a newly created blog home slider.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

            $slider = BlogHome::create($validatedData);

            return response()->json([
                'message' => 'Blog home slider created successfully.',
                'slider' => $slider
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating blog home slider: ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified blog home slider.
     */
    public function show($blog_home_id)
    {
        // findOrFail automatically handles the 'not found' case by throwing an exception,
        // which Laravel's handler converts to a 404 response.
        return BlogHome::findOrFail($blog_home_id);
    }

    /**
     * Update the specified blog home slider.
     */
    public function update(Request $request, $blog_home_id)
    {
        // findOrFail ensures the slider exists before proceeding.
        $slider = BlogHome::findOrFail($blog_home_id);

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
                'message' => 'Blog home slider updated successfully.',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating blog home slider ID {$blog_home_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred during the update.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified blog home slider from storage.
     */
    public function destroy($blog_home_id)
    {
        // findOrFail ensures the slider exists before attempting to delete.
        $slider = BlogHome::findOrFail($blog_home_id);

        try {
            // Delete the associated image file if it exists.
            if ($slider->home_img) {
                File::delete(public_path($slider->home_img));
            }

            $slider->delete();

            // Return a 204 No Content response, which is the standard for a successful deletion.
            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting blog home slider ID {$blog_home_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred during deletion.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}