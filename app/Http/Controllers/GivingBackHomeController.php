<?php

namespace App\Http\Controllers;

use App\Models\GivingBackHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class GivingBackHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'givingBackHomeSlider']);
    }

    public function index()
    {
        try {
            \Log::info('Fetching all giving back home sliders');
            $sliders = GivingBackHome::orderBy('giving_back_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching giving back home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch giving back home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function givingBackHomeSlider()
    {
        try {
            \Log::info('Fetching all giving back home sliders for public view');
            $sliders = GivingBackHome::orderBy('giving_back_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching giving back home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch giving back home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        \Log::info('Giving back home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for giving back home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/giving_back_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('Giving back home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = GivingBackHome::create($validatedData);
            \Log::info('Giving back home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'Giving back home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating giving back home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create giving back home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($giving_back_id)
    {
        \Log::info('Fetching giving back home slider with ID: ' . $giving_back_id);
        try {
            $slider = GivingBackHome::findOrFail($giving_back_id);
            \Log::info('Giving back home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Giving back home slider not found for ID: ' . $giving_back_id);
            return response()->json(['error' => 'Giving back home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching giving back home slider for ID ' . $giving_back_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve giving back home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $giving_back_id)
    {
        \Log::info('Giving back home slider update request data for ID ' . $giving_back_id . ': ', $request->all());

        $slider = GivingBackHome::find($giving_back_id);
        if (!$slider) {
            \Log::warning('Giving back home slider not found for update, ID: ' . $giving_back_id);
            return response()->json(['error' => 'Giving back home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for giving back home slider update ID ' . $giving_back_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                if ($image->getError() !== UPLOAD_ERR_OK) {
                    \Log::warning('File upload error for giving back home slider ID ' . $giving_back_id . ': ' . $image->getErrorMessage());
                    return response()->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
                }

                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old giving back home slider image: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old giving back home slider image not found for deletion: ' . $oldImagePath);
                    }
                }

                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/giving_back_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New giving back home slider image uploaded: ' . $validatedData['home_img']);
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing giving back home slider image (null input): ' . $oldImagePath);
                    } else {
                        \Log::warning('Existing giving back home slider image not found for deletion (null input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
                \Log::info('Giving back home slider image field set to null');
            } else {
                \Log::info('No new image uploaded or null specified, preserving existing image: ' . ($slider->home_img ?: 'none'));
            }

            $slider->fill($validatedData)->save();
            \Log::info('Giving back home slider updated successfully for ID: ' . $giving_back_id);

            return response()->json([
                'message' => 'Giving back home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating giving back home slider for ID ' . $giving_back_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update giving back home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($giving_back_id)
    {
        $slider = GivingBackHome::find($giving_back_id);
        if (!$slider) {
            \Log::warning('Giving back home slider not found for deletion, ID: ' . $giving_back_id);
            return response()->json(['error' => 'Giving back home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted giving back home slider image: ' . $imagePath);
                } else {
                    \Log::warning('Giving back home slider image not found for deletion: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('Giving back home slider deleted successfully for ID: ' . $giving_back_id);
            return response()->json(['message' => 'Giving back home slider deleted successfully'], Response::HTTP_NO_CONTENT);
        } catch (Exception $e) {
            \Log::error('Error deleting giving back home slider for ID ' . $giving_back_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete giving back home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}