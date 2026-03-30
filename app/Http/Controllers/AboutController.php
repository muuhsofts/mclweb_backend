<?php

namespace App\Http\Controllers;

use App\Models\About;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class AboutController extends Controller
{
    /**
     * The path relative to the public directory where about images are stored.
     * It is assumed this directory already exists on the server.
     */
    private const IMAGE_UPLOAD_PATH = 'uploads/about_images';

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'AboutSliders']);
    }

    /**
     * Get all about entries.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            Log::info('Fetching all about entries (newest first)');
            $about = About::orderByDesc('about_id')->get();
            return response()->json($about, Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error fetching about entries: ' . $e->getMessage());
            return response()->json(
                ['error' => 'Failed to retrieve about entries', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get all about entries for sliders (functionally identical to index).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function AboutSliders()
    {
        try {
            Log::info('Fetching all about slider entries (newest first)');
            $about = About::orderByDesc('about_id')->get();
            return response()->json($about, Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error fetching about slider entries: ' . $e->getMessage());
            return response()->json(
                ['error' => 'Failed to retrieve about entries', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a new about entry.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('About store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:16048', // Max 16MB
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for About store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            // Handle home_img upload
            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                
                // Sanitize filename and ensure uniqueness
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();
                
                // Move the image to the predefined public path
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                
                // Store path relative to public directory
                $validatedData['home_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
                Log::info('About image uploaded to: ' . $validatedData['home_img']);
            }

            $about = About::create($validatedData);
            Log::info('About entry created successfully: ', $about->toArray());
            return response()->json(['message' => 'About entry created successfully', 'about' => $about], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Error creating about entry: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create about entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a single about entry by ID.
     *
     * @param  int  $about_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($about_id)
    {
        Log::info('Fetching about entry with ID: ' . $about_id);
        try {
            $about = About::findOrFail($about_id);
            Log::info('About entry found: ', $about->toArray());
            return response()->json($about, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('About entry not found for ID: ' . $about_id);
            return response()->json(['error' => 'About entry not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error fetching about entry for ID ' . $about_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve about entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing about entry.
     * Note: This method is designed for 'x-www-form-urlencoded' or 'form-data' to handle file uploads correctly.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $about_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $about_id)
    {
        Log::info('About update request data for ID ' . $about_id . ': ', $request->all());

        $about = About::find($about_id);
        if (!$about) {
            Log::warning('About entry not found for ID: ' . $about_id);
            return response()->json(['message' => 'About entry not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:16048', // Max 16MB
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for About update ID ' . $about_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                // Delete the old image if it exists
                if ($about->home_img && File::exists(public_path($about->home_img))) {
                    File::delete(public_path($about->home_img));
                    Log::info('Deleted old about image: ' . $about->home_img);
                }

                // Upload the new image
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();
                
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['home_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
                Log::info('New about image uploaded to: ' . $validatedData['home_img']);
            
            // This case handles when 'home_img' is sent as null or empty to remove the image.
            } else if ($request->has('home_img') && $request->input('home_img') == null) {
                if ($about->home_img && File::exists(public_path($about->home_img))) {
                    File::delete(public_path($about->home_img));
                    Log::info('Deleted existing about image (due to null input): ' . $about->home_img);
                }
                $validatedData['home_img'] = null;
            }

            $about->fill($validatedData)->save();
            Log::info('About entry updated successfully for ID: ' . $about_id);
            return response()->json([
                'message' => 'About entry updated successfully',
                'about' => $about->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error updating about entry for ID ' . $about_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update about entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete an about entry.
     *
     * @param  int  $about_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($about_id)
    {
        $about = About::find($about_id);
        if (!$about) {
            Log::warning('About entry not found for ID: ' . $about_id);
            return response()->json(['error' => 'About entry not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Delete image if it exists
            if ($about->home_img) {
                $imagePath = public_path($about->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    Log::info('Deleted about image from public path: ' . $imagePath);
                } else {
                    Log::warning('About image not found at public path for deletion: ' . $imagePath);
                }
            }

            $about->delete();
            Log::info('About entry deleted successfully for ID: ' . $about_id);
            return response()->json(null, Response::HTTP_NO_CONTENT); // Standard for successful deletion
        } catch (Exception $e) {
            Log::error('Error deleting about entry for ID ' . $about_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete about entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Count all about entries.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countAbout()
    {
        try {
            Log::info('Counting about entries');
            $count = About::count();
            return response()->json(['about_entries' => $count], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error counting about entries: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count about entries', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch specific fields for dropdown lists.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDropdownOptions()
    {
        try {
            Log::info('Fetching about dropdown options');
            $about = About::select('about_id', 'description', 'heading')->get();
            return response()->json($about, Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error fetching about dropdown options: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch dropdown options', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}