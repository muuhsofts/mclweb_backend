<?php

namespace App\Http\Controllers;

use App\Models\ContactHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class ContactHomeController extends Controller
{
    /**
     * The path for storing uploaded images.
     * Note: Sticking with 'Uploads' to match the original controller. Convention is usually lowercase.
     */
    private const IMAGE_UPLOAD_PATH = 'Uploads/contact_home';

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'contactHomeSlider']);
    }

    /**
     * Display a listing of contact home sliders for admin view.
     */
    public function index()
    {
        // Return the collection directly. Laravel automatically converts it to JSON.
        return ContactHome::orderBy('cont_home_id', 'desc')->get();
    }

    /**
     * Display contact home sliders for public view.
     */
    public function contactHomeSlider()
    {
        // This endpoint is public via middleware.
        return ContactHome::orderBy('cont_home_id', 'desc')->get();
    }

    /**
     * Store a newly created contact home slider.
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

            $slider = ContactHome::create($validatedData);

            return response()->json([
                'message' => 'Contact home slider created successfully.',
                'slider' => $slider
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating contact home slider: ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified contact home slider.
     *
     * @param  \App\Models\ContactHome  $contactHome (Route-Model Binding)
     * @return \App\Models\ContactHome
     */
    public function show(ContactHome $contactHome)
    {
        // Thanks to route-model binding, the object is already fetched or a 404 was thrown.
        return $contactHome;
    }

    /**
     * Update the specified contact home slider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ContactHome  $contactHome (Route-Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ContactHome $contactHome)
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

            // Handle the file update logic cleanly
            if ($request->hasFile('home_img')) {
                // 1. New file uploaded: Delete old and store new
                if ($contactHome->home_img) {
                    File::delete(public_path($contactHome->home_img));
                }
                $image = $request->file('home_img');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['home_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                // 2. Explicitly removing the file by sending `null`
                if ($contactHome->home_img) {
                    File::delete(public_path($contactHome->home_img));
                }
                $validatedData['home_img'] = null;
            }
            // 3. No file change: The 'home_img' key is not in validatedData, so it won't be updated.

            $contactHome->update($validatedData);

            return response()->json([
                'message' => 'Contact home slider updated successfully.',
                'slider' => $contactHome->fresh()
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating contact home slider ID {$contactHome->cont_home_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred during the update.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified contact home slider.
     *
     * @param  \App\Models\ContactHome  $contactHome (Route-Model Binding)
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContactHome $contactHome)
    {
        try {
            // Delete the associated image file if it exists.
            if ($contactHome->home_img) {
                File::delete(public_path($contactHome->home_img));
            }

            $contactHome->delete();

            // Return a 204 No Content response, which is the standard for a successful deletion.
            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting contact home slider ID {$contactHome->cont_home_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred during deletion.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}