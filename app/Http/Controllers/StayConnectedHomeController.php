<?php

namespace App\Http\Controllers;

use App\Models\StayConnectedHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class StayConnectedHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'stayConnectedHomeSlider']);
    }

    /**
     * Display a listing of stay connected home sliders.
     */
    public function index()
    {
        try {
            \Log::info('Fetching all stay connected home sliders');
            $sliders = StayConnectedHome::orderBy('stay_connected_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching stay connected home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch stay connected home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display stay connected home sliders for public view.
     */
    public function stayConnectedHomeSlider()
    {
        try {
            \Log::info('Fetching all stay connected home sliders for public view');
            $sliders = StayConnectedHome::orderBy('stay_connected_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching stay connected home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch stay connected home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created stay connected home slider.
     */
    public function store(Request $request)
    {
        \Log::info('Stay connected home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for stay connected home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/stay_connected_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('Stay connected home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = StayConnectedHome::create($validatedData);
            \Log::info('Stay connected home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'Stay connected home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating stay connected home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create stay connected home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified stay connected home slider.
     */
    public function show($stay_connected_id)
    {
        \Log::info('Fetching stay connected home slider with ID: ' . $stay_connected_id);
        try {
            $slider = StayConnectedHome::findOrFail($stay_connected_id);
            \Log::info('Stay connected home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Stay connected home slider not found for ID: ' . $stay_connected_id);
            return response()->json(['error' => 'Stay connected home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching stay connected home slider for ID ' . $stay_connected_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve stay connected home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified stay connected home slider.
     */
    public function update(Request $request, $stay_connected_id)
    {
        \Log::info('Stay connected home slider update request data for ID ' . $stay_connected_id . ': ', $request->all());

        $slider = StayConnectedHome::find($stay_connected_id);
        if (!$slider) {
            \Log::warning('Stay connected home slider not found for update, ID: ' . $stay_connected_id);
            return response()->json(['error' => 'Stay connected home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for stay connected home slider update ID ' . $stay_connected_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                if ($image->getError() !== UPLOAD_ERR_OK) {
                    \Log::warning('File upload error for stay connected home slider ID ' . $stay_connected_id . ': ' . $image->getErrorMessage());
                    return response()->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
                }

                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old stay connected home slider image: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old stay connected home slider image not found for deletion: ' . $oldImagePath);
                    }
                }

                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/stay_connected_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New stay connected home slider image uploaded: ' . $validatedData['home_img']);
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing stay connected home slider image (null input): ' . $oldImagePath);
                    } else {
                        \Log::warning('Existing stay connected home slider image not found for deletion (null input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
                \Log::info('Stay connected home slider image field set to null');
            } else {
                \Log::info('No new image uploaded or null specified, preserving existing image: ' . ($slider->home_img ?: 'none'));
            }

            $slider->fill($validatedData)->save();
            \Log::info('Stay connected home slider updated successfully for ID: ' . $stay_connected_id);

            return response()->json([
                'message' => 'Stay connected home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating stay connected home slider for ID ' . $stay_connected_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update stay connected home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified stay connected home slider.
     */
    public function destroy($stay_connected_id)
    {
        $slider = StayConnectedHome::find($stay_connected_id);
        if (!$slider) {
            \Log::warning('Stay connected home slider not found for deletion, ID: ' . $stay_connected_id);
            return response()->json(['error' => 'Stay connected home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted stay connected home slider image: ' . $imagePath);
                } else {
                    \Log::warning('Stay connected home slider image not found for deletion: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('Stay connected home slider deleted successfully for ID: ' . $stay_connected_id);
            return response()->json(['message' => 'Stay connected home slider deleted successfully'], Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            \Log::error('Error deleting stay connected home slider for ID ' . $stay_connected_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete stay connected home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}