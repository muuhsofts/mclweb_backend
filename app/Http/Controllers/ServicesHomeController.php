<?php

namespace App\Http\Controllers;

use App\Models\ServicesHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class ServicesHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'servicesHomeSlider','latestService']);
    }

    /**
     * Display a listing of services home sliders.
     */
    public function index()
    {
        try {
            \Log::info('Fetching all services home sliders');
            $sliders = ServicesHome::orderBy('services_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching services home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch services home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display services home sliders for public view.
     */
    public function servicesHomeSlider()
    {
        try {
            \Log::info('Fetching all services home sliders for public view');
            $sliders = ServicesHome::orderBy('services_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching services home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch services home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function latestService()
{
    try {
        \Log::info('Fetching the latest service home slider for public view');
        $slider = ServicesHome::orderBy('services_home_id', 'desc')->first();
        \Log::info('Retrieved latest slider: ', ['slider_id' => $slider?->services_home_id]);
        return response()->json($slider ?: [], Response::HTTP_OK);
    } catch (Exception $e) {
        \Log::error('Error fetching latest service home slider: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch latest service home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
     * Store a newly created services home slider.
     */
    public function store(Request $request)
    {
        \Log::info('Services home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for services home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('Services home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = ServicesHome::create($validatedData);
            \Log::info('Services home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'Services home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating services home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create services home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified services home slider.
     */
    public function show($services_home_id)
    {
        \Log::info('Fetching services home slider with ID: ' . $services_home_id);
        try {
            $slider = ServicesHome::findOrFail($services_home_id);
            \Log::info('Services home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Services home slider not found for ID: ' . $services_home_id);
            return response()->json(['error' => 'Services home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching services home slider for ID ' . $services_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve services home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified services home slider.
     */
    public function update(Request $request, $services_home_id)
    {
        \Log::info('Services home slider update request data for ID ' . $services_home_id . ': ', $request->all());

        $slider = ServicesHome::find($services_home_id);
        if (!$slider) {
            \Log::warning('Services home slider not found for update, ID: ' . $services_home_id);
            return response()->json(['error' => 'Services home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for services home slider update ID ' . $services_home_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                if ($image->getError() !== UPLOAD_ERR_OK) {
                    \Log::warning('File upload error for services home slider ID ' . $services_home_id . ': ' . $image->getErrorMessage());
                    return response()->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
                }

                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old services home slider image: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old services home slider image not found for deletion: ' . $oldImagePath);
                    }
                }

                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New services home slider image uploaded: ' . $validatedData['home_img']);
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing services home slider image (null input): ' . $oldImagePath);
                    } else {
                        \Log::warning('Existing services home slider image not found for deletion (null input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
                \Log::info('Services home slider image field set to null');
            } else {
                \Log::info('No new image uploaded or null specified, preserving existing image: ' . ($slider->home_img ?: 'none'));
            }

            $slider->fill($validatedData)->save();
            \Log::info('Services home slider updated successfully for ID: ' . $services_home_id);

            return response()->json([
                'message' => 'Services home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating services home slider for ID ' . $services_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update services home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified services home slider.
     */
    public function destroy($services_home_id)
    {
        $slider = ServicesHome::find($services_home_id);
        if (!$slider) {
            \Log::warning('Services home slider not found for deletion, ID: ' . $services_home_id);
            return response()->json(['error' => 'Services home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted services home slider image: ' . $imagePath);
                } else {
                    \Log::warning('Services home slider image not found for deletion: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('Services home slider deleted successfully for ID: ' . $services_home_id);
            return response()->json(['message' => 'Services home slider deleted successfully'], Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            \Log::error('Error deleting services home slider for ID ' . $services_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete services home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}