<?php

namespace App\Http\Controllers;

use App\Models\SustainabilityHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class SustainabilityHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'sustainabilityHomeSlider']);
    }

    public function index()
    {
        try {
            \Log::info('Fetching all sustainability home sliders');
            $sliders = SustainabilityHome::orderBy('sustainability_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching sustainability home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sustainability home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sustainabilityHomeSlider()
    {
        try {
            \Log::info('Fetching all sustainability home sliders for public view');
            $sliders = SustainabilityHome::orderBy('sustainability_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching sustainability home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sustainability home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        \Log::info('Sustainability home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for sustainability home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/sustainability_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('Sustainability home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = SustainabilityHome::create($validatedData);
            \Log::info('Sustainability home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'Sustainability home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating sustainability home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create sustainability home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($sustainability_home_id)
    {
        \Log::info('Fetching sustainability home slider with ID: ' . $sustainability_home_id);
        try {
            $slider = SustainabilityHome::findOrFail($sustainability_home_id);
            \Log::info('Sustainability home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Sustainability home slider not found for ID: ' . $sustainability_home_id);
            return response()->json(['error' => 'Sustainability home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching sustainability home slider for ID ' . $sustainability_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve sustainability home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $sustainability_home_id)
    {
        \Log::info('Sustainability home slider update request data for ID ' . $sustainability_home_id . ': ', $request->all());

        $slider = SustainabilityHome::find($sustainability_home_id);
        if (!$slider) {
            \Log::warning('Sustainability home slider not found for update, ID: ' . $sustainability_home_id);
            return response()->json(['error' => 'Sustainability home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for sustainability home slider update ID ' . $sustainability_home_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                if ($image->getError() !== UPLOAD_ERR_OK) {
                    \Log::warning('File upload error for sustainability home slider ID ' . $sustainability_home_id . ': ' . $image->getErrorMessage());
                    return response()->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
                }

                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old sustainability home slider image: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old sustainability home slider image not found for deletion: ' . $oldImagePath);
                    }
                }

                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/sustainability_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New sustainability home slider image uploaded: ' . $validatedData['home_img']);
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing sustainability home slider image (null input): ' . $oldImagePath);
                    } else {
                        \Log::warning('Existing sustainability home slider image not found for deletion (null input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
                \Log::info('Sustainability home slider image field set to null');
            } else {
                \Log::info('No new image uploaded or null specified, preserving existing image: ' . ($slider->home_img ?: 'none'));
            }

            $slider->fill($validatedData)->save();
            \Log::info('Sustainability home slider updated successfully for ID: ' . $sustainability_home_id);

            return response()->json([
                'message' => 'Sustainability home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating sustainability home slider for ID ' . $sustainability_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update sustainability home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($sustainability_home_id)
    {
        $slider = SustainabilityHome::find($sustainability_home_id);
        if (!$slider) {
            \Log::warning('Sustainability home slider not found for deletion, ID: ' . $sustainability_home_id);
            return response()->json(['error' => 'Sustainability home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted sustainability home slider image: ' . $imagePath);
                } else {
                    \Log::warning('Sustainability home slider image not found for deletion: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('Sustainability home slider deleted successfully for ID: ' . $sustainability_home_id);
            return response()->json(['message' => 'Sustainability home slider deleted successfully'], Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            \Log::error('Error deleting sustainability home slider for ID ' . $sustainability_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete sustainability home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}