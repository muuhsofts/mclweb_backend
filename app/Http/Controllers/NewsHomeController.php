<?php

namespace App\Http\Controllers;

use App\Models\NewsHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class NewsHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'newsHomeSlider']);
    }

    /**
     * Display a listing of news home sliders.
     */
    public function index()
    {
        try {
            \Log::info('Fetching all news home sliders');
            $sliders = NewsHome::orderBy('news_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching news home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display news home sliders for public view.
     */
    public function newsHomeSlider()
    {
        try {
            \Log::info('Fetching all news home sliders for public view');
            $sliders = NewsHome::orderBy('news_home_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching news home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created news home slider.
     */
    public function store(Request $request)
    {
        \Log::info('News home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for news home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/news_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('News home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = NewsHome::create($validatedData);
            \Log::info('News home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'News home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating news home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create news home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified news home slider.
     */
    public function show($news_home_id)
    {
        \Log::info('Fetching news home slider with ID: ' . $news_home_id);
        try {
            $slider = NewsHome::findOrFail($news_home_id);
            \Log::info('News home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('News home slider not found for ID: ' . $news_home_id);
            return response()->json(['error' => 'News home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching news home slider for ID ' . $news_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve news home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified news home slider.
     */
    public function update(Request $request, $news_home_id)
    {
        \Log::info('News home slider update request data for ID ' . $news_home_id . ': ', $request->all());

        $slider = NewsHome::find($news_home_id);
        if (!$slider) {
            \Log::warning('News home slider not found for update, ID: ' . $news_home_id);
            return response()->json(['error' => 'News home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for news home slider update ID ' . $news_home_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();
            \Log::info('Validated data for update: ', $validatedData);

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                if ($image->getError() !== UPLOAD_ERR_OK) {
                    \Log::warning('File upload error for news home slider ID ' . $news_home_id . ': ' . $image->getErrorMessage());
                    return response()->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
                }

                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old news home slider image: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old news home slider image not found for deletion: ' . $oldImagePath);
                    }
                }

                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'Uploads/news_homes';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New news home slider image uploaded: ' . $validatedData['home_img']);
            } elseif ($request->has('home_img') && $request->input('home_img') === '') {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing news home slider image (empty input): ' . $oldImagePath);
                    } else {
                        \Log::warning('Existing news home slider image not found for deletion (empty input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
                \Log::info('News home slider image field set to null');
            } else {
                \Log::info('No new image uploaded or empty string specified, preserving existing image: ' . ($slider->home_img ?: 'none'));
                unset($validatedData['home_img']); // Prevent overwriting with null
            }

            // Only update provided fields
            $slider->fill(array_filter($validatedData, fn($value) => $value !== null))->save();
            \Log::info('News home slider updated successfully for ID: ' . $news_home_id);

            return response()->json([
                'message' => 'News home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating news home slider for ID ' . $news_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update news home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified news home slider.
     */
    public function destroy($news_home_id)
    {
        $slider = NewsHome::find($news_home_id);
        if (!$slider) {
            \Log::warning('News home slider not found for deletion, ID: ' . $news_home_id);
            return response()->json(['error' => 'News home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted news home slider image: ' . $imagePath);
                } else {
                    \Log::warning('News home slider image not found for deletion: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('News home slider deleted successfully for ID: ' . $news_home_id);
            return response()->json(['message' => 'News home slider deleted successfully'], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error deleting news home slider for ID ' . $news_home_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete news home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}