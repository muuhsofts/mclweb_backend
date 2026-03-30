<?php

namespace App\Http\Controllers;

use App\Models\DiversityHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class DiversityHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'homeSlider']);
    }

    /**
     * Get all diversity_home entries.
     */
    public function index(): JsonResponse
    {
        try {
            \Log::info('Fetching all diversity home entries');
            $diversityHomes = DiversityHome::all();
            \Log::info('Retrieved diversity home entries: ', $diversityHomes->toArray());
            return response()->json($diversityHomes, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching diversity home entries: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve diversity home entries', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all diversity_home entries for slider.
     */
    public function homeSlider(): JsonResponse
    {
        try {
            \Log::info('Fetching diversity home entries for slider');
            $diversityHomes = DiversityHome::all();
            \Log::info('Retrieved diversity home entries for slider: ', $diversityHomes->toArray());
            return response()->json($diversityHomes, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching diversity home entries for slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve diversity home entries for slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a new diversity_home entry.
     */
    public function store(Request $request): JsonResponse
    {
        \Log::info('Diversity home store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for diversity home store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'uploads/diversity_home_images';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('Diversity home image uploaded to public/uploads: ' . $validatedData['home_img']);
            }

            $diversityHome = DiversityHome::create($validatedData);
            \Log::info('Diversity home entry created successfully: ', $diversityHome->toArray());
            return response()->json(['message' => 'Diversity home entry created successfully', 'diversity_home' => $diversityHome], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating diversity home entry: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create diversity home entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a single diversity_home entry.
     */
    public function show($dhome_id): JsonResponse
    {
        \Log::info('Fetching diversity home entry with ID: ' . $dhome_id);
        try {
            $diversityHome = DiversityHome::findOrFail($dhome_id);
            \Log::info('Diversity home entry found: ', $diversityHome->toArray());
            return response()->json($diversityHome, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Diversity home entry not found for ID: ' . $dhome_id);
            return response()->json(['error' => 'Diversity home entry not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching diversity home entry for ID ' . $dhome_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve diversity home entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a diversity_home entry.
     */
    public function update(Request $request, $dhome_id): JsonResponse
    {
        \Log::info('Diversity home update request data for ID ' . $dhome_id . ': ', $request->all());

        $diversityHome = DiversityHome::find($dhome_id);
        if (!$diversityHome) {
            \Log::warning('Diversity home entry not found for ID: ' . $dhome_id);
            return response()->json(['error' => 'Diversity home entry not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for diversity home update ID ' . $dhome_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                if ($diversityHome->home_img) {
                    $oldImagePath = public_path($diversityHome->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old diversity home image from public path: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old diversity home image not found at public path for deletion: ' . $oldImagePath);
                    }
                }

                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = 'uploads/diversity_home_images';
                $publicDestinationPath = public_path($destinationFolder);

                if (!File::isDirectory($publicDestinationPath)) {
                    File::makeDirectory($publicDestinationPath, 0755, true, true);
                }

                $image->move($publicDestinationPath, $imageName);
                $validatedData['home_img'] = $destinationFolder . '/' . $imageName;
                \Log::info('New diversity home image uploaded to public/uploads: ' . $validatedData['home_img']);
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                if ($diversityHome->home_img) {
                    $oldImagePath = public_path($diversityHome->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing diversity home image (due to null input) from public path: ' . $oldImagePath);
                    } else {
                        \Log::warning('Old diversity home image not found at public path for removal: ' . $oldImagePath);
                    }
                }
                \Log::info('Diversity home image field was set to null. Old image (if any) deleted.');
            }

            $diversityHome->fill($validatedData)->save();
            \Log::info('Diversity home entry updated successfully for ID: ' . $dhome_id);
            return response()->json([
                'message' => 'Diversity home entry updated successfully',
                'diversity_home' => $diversityHome->fresh(),
                'image_path' => $diversityHome->home_img ? asset($diversityHome->home_img) : null,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating diversity home entry for ID ' . $dhome_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update diversity home entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a diversity_home entry.
     */
    public function destroy($dhome_id): JsonResponse
    {
        \Log::info('Attempting to delete diversity home entry with ID: ' . $dhome_id);
        $diversityHome = DiversityHome::find($dhome_id);
        if (!$diversityHome) {
            \Log::warning('Diversity home entry not found for ID: ' . $dhome_id);
            return response()->json(['error' => 'Diversity home entry not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($diversityHome->home_img) {
                $imagePath = public_path($diversityHome->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted diversity home image from public path: ' . $imagePath);
                } else {
                    \Log::warning('Diversity home image not found at public path for deletion: ' . $imagePath);
                }
            }

            $diversityHome->delete();
            \Log::info('Diversity home entry deleted successfully for ID: ' . $dhome_id);
            return response()->json(['message' => 'Diversity home entry deleted successfully'], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error deleting diversity home entry for ID ' . $dhome_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete diversity home entry', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Count diversity_home entries.
     */
    public function countDiversityHome(): JsonResponse
    {
        try {
            \Log::info('Counting diversity home entries');
            $count = DiversityHome::count();
            \Log::info('Diversity home entries count: ' . $count);
            return response()->json(['diversity_home_entries' => $count], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error counting diversity home entries: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count diversity home entries', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get dropdown options for diversity_home entries.
     */
    public function getDropdownOptions(): JsonResponse
    {
        try {
            \Log::info('Fetching diversity home dropdown options');
            $diversityHomes = DiversityHome::select('dhome_id', 'heading')->distinct()->get();
            \Log::info('Retrieved diversity home dropdown options: ', $diversityHomes->toArray());
            return response()->json($diversityHomes, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching diversity home dropdown options: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch dropdown options', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}