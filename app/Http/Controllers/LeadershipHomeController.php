<?php

namespace App\Http\Controllers;

use App\Models\LeadershipHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class LeadershipHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'leadershipHomeSlider']);
    }

    /**
     * Display a listing of leadership home sliders.
     */
    public function index()
    {
        try {
            \Log::info('Fetching all leadership home sliders');
            $sliders = LeadershipHome::orderBy('leadership_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching leadership home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch leadership home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display leadership home sliders for public view.
     */
    public function leadershipHomeSlider()
    {
        try {
            \Log::info('Fetching all leadership home sliders for public view');
            $sliders = LeadershipHome::orderBy('leadership_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching leadership home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch leadership home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created leadership home slider.
     */
    public function store(Request $request)
    {
        \Log::info('Leadership home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for leadership home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/leadership_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('Leadership home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = LeadershipHome::create($validatedData);
            \Log::info('Leadership home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'Leadership home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating leadership home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create leadership home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified leadership home slider.
     */
    public function show($leadership_home_id)
    {
        \Log::info('Fetching leadership home slider with ID: ' . $leadership_home_id);
        try {
            $slider = LeadershipHome::findOrFail($leadership_home_id);
            \Log::info('Leadership home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Leadership home slider not found for ID: ' . $leadership_home_id);
            return response()->json(['error' => 'Leadership home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching leadership home slider for ID ' . $leadership_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve leadership home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified leadership home slider.
     */
    public function update(Request $request, $leadership_home_id)
    {
        \Log::info('Leadership home slider update request data for ID ' . $leadership_home_id . ': ', $request->all());

        $slider = LeadershipHome::find($leadership_home_id);
        if (!$slider) {
            \Log::warning('Leadership home slider not found for update, ID: ' . $leadership_home_id);
            return response()->json(['error' => 'Leadership home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for leadership home slider update ID ' . $leadership_home_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                if ($image->getError() !== UPLOAD_ERR_OK) {
                    \Log::warning('File upload error for leadership home slider ID ' . $leadership_home_id . ': ' . $image->getErrorMessage());
                    return response()->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
                }

                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old leadership home slider image: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old leadership home slider image not found for deletion: ' . $oldImagePath);
                    }
                }

                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/leadership_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New leadership home slider image uploaded: ' . $validatedData['home_img']);
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing leadership home slider image (null input): ' . $oldImagePath);
                    } else {
                        \Log::warning('Existing leadership home slider image not found for deletion (null input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
                \Log::info('Leadership home slider image field set to null');
            } else {
                \Log::info('No new image uploaded or null specified, preserving existing image: ' . ($slider->home_img ?: 'none'));
            }

            $slider->fill($validatedData)->save();
            \Log::info('Leadership home slider updated successfully for ID: ' . $leadership_home_id);

            return response()->json([
                'message' => 'Leadership home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating leadership home slider for ID ' . $leadership_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update leadership home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified leadership home slider.
     */
    public function destroy($leadership_home_id)
    {
        $slider = LeadershipHome::find($leadership_home_id);
        if (!$slider) {
            \Log::warning('Leadership home slider not found for deletion, ID: ' . $leadership_home_id);
            return response()->json(['error' => 'Leadership home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted leadership home slider image: ' . $imagePath);
                } else {
                    \Log::warning('Leadership home slider image not found for deletion: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('Leadership home slider deleted successfully for ID: ' . $leadership_home_id);
            return response()->json(['message' => 'Leadership home slider deleted successfully'], Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            \Log::error('Error deleting leadership home slider for ID ' . $leadership_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete leadership home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}