<?php

namespace App\Http\Controllers;

use App\Models\EarycareHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class EarycareHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'earycareHomeSlider']);
    }

    /**
     * Display a listing of earycare home sliders.
     */
    public function index()
    {
        try {
            \Log::info('Fetching all earycare home sliders');
            $sliders = EarycareHome::orderBy('earycare_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching earycare home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch earycare home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display earycare home sliders for public view.
     */
    public function earycareHomeSlider()
    {
        try {
            \Log::info('Fetching all earycare home sliders for public view');
            $sliders = EarycareHome::orderBy('earycare_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching earycare home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch earycare home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created earycare home slider.
     */
    public function store(Request $request)
    {
        \Log::info('Earycare home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for earycare home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/earycare_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('Earycare home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = EarycareHome::create($validatedData);
            \Log::info('Earycare home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'Earycare home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating earycare home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create earycare home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified earycare home slider.
     */
    public function show($earycare_id)
    {
        \Log::info('Fetching earycare home slider with ID: ' . $earycare_id);
        try {
            $slider = EarycareHome::findOrFail($earycare_id);
            \Log::info('Earycare home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Earycare home slider not found for ID: ' . $earycare_id);
            return response()->json(['error' => 'Earycare home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching earycare home slider for ID ' . $earycare_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve earycare home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified earycare home slider.
     */
    public function update(Request $request, $earycare_id)
    {
        \Log::info('Earycare home slider update request data for ID ' . $earycare_id . ': ', $request->all());

        $slider = EarycareHome::find($earycare_id);
        if (!$slider) {
            \Log::warning('Earycare home slider not found for update, ID: ' . $earycare_id);
            return response()->json(['error' => 'Earycare home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for earycare home slider update ID ' . $earycare_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                if ($image->getError() !== UPLOAD_ERR_OK) {
                    \Log::warning('File upload error for earycare home slider ID ' . $earycare_id . ': ' . $image->getErrorMessage());
                    return response()->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
                }

                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old earycare home slider image: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old earycare home slider image not found for deletion: ' . $oldImagePath);
                    }
                }

                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/earycare_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New earycare home slider image uploaded: ' . $validatedData['home_img']);
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing earycare home slider image (null input): ' . $oldImagePath);
                    } else {
                        \Log::warning('Existing earycare home slider image not found for deletion (null input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
                \Log::info('Earycare home slider image field set to null');
            } else {
                \Log::info('No new image uploaded or null specified, preserving existing image: ' . ($slider->home_img ?: 'none'));
            }

            $slider->fill($validatedData)->save();
            \Log::info('Earycare home slider updated successfully for ID: ' . $earycare_id);

            return response()->json([
                'message' => 'Earycare home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating earycare home slider for ID ' . $earycare_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update earycare home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified earycare home slider.
     */
    public function destroy($earycare_id)
    {
        $slider = EarycareHome::find($earycare_id);
        if (!$slider) {
            \Log::warning('Earycare home slider not found for deletion, ID: ' . $earycare_id);
            return response()->json(['error' => 'Earycare home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted earycare home slider image: ' . $imagePath);
                } else {
                    \Log::warning('Earycare home slider image not found for deletion: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('Earycare home slider deleted successfully for ID: ' . $earycare_id);
            return response()->json(['message' => 'Earycare home slider deleted successfully'], Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            \Log::error('Error deleting earycare home slider for ID ' . $earycare_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete earycare home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}