<?php

namespace App\Http\Controllers;

use App\Models\GivingBack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class GivingBackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestGivingBack', 'allGivingBack']);
    }

    private function ensureUploadsDirectory(string $subdirectory = 'giving_back_images'): bool
    {
        $uploadPath = public_path('uploads/' . $subdirectory);
        if (!file_exists($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                \Log::error('Failed to create directory: ' . $uploadPath);
                return false;
            }
        }
        if (!is_writable($uploadPath)) {
            \Log::error('Directory is not writable: ' . $uploadPath);
            return false;
        }
        return true;
    }

    public function index()
    {
        try {
            $givingBackRecords = GivingBack::orderBy('giving_id', 'desc')->get();
            return response()->json(['giving_back' => $givingBackRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching giving back records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch giving back records.', 'details' => $e->getMessage()], 500);
        }
    }

    public function allGivingBack()
    {
        try {
            $givingBackRecords = GivingBack::orderBy('giving_id', 'desc')->get();
            return response()->json(['giving_back' => $givingBackRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching giving back records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch giving back records.', 'details' => $e->getMessage()], 500);
        }
    }

    public function latestGivingBack()
    {
        try {
            $latestGivingBack = GivingBack::orderBy('created_at', 'desc')->first();
            if (!$latestGivingBack) {
                return response()->json(['message' => 'No Giving Back record found'], 404);
            }
            return response()->json(['giving_back' => $latestGivingBack], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest giving back record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest giving back record.', 'details' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        \Log::info('Giving Back store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'givingBack_category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weblink' => 'nullable|url|max:255',
            'image_slider.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Giving Back store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$this->ensureUploadsDirectory()) {
            return response()->json(['error' => 'Unable to access image upload directory.'], 500);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('image_slider')) {
                $imagePaths = [];
                foreach ($request->file('image_slider') as $image) {
                    if ($image->isValid()) {
                        $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('uploads/giving_back_images'), $fileName);
                        $imagePaths[] = 'uploads/giving_back_images/' . $fileName;
                        \Log::info('Image uploaded: uploads/giving_back_images/' . $fileName);
                    }
                }
                $data['image_slider'] = !empty($imagePaths) ? json_encode($imagePaths) : null;
            }

            $givingBack = GivingBack::create($data);
            return response()->json(['message' => 'Giving Back record created successfully', 'giving_back' => $givingBack], 201);
        } catch (Exception $e) {
            \Log::error('Error creating giving back record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create giving back record.', 'details' => $e->getMessage()], 500);
        }
    }

    public function show($giving_id)
    {
        try {
            $givingBack = GivingBack::find($giving_id);
            if (!$givingBack) {
                return response()->json(['message' => 'Giving Back record not found'], 404);
            }
            return response()->json(['giving_back' => $givingBack], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching giving back record ID ' . $giving_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch giving back record.', 'details' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $giving_id)
    {
        \Log::info('Giving Back update request data for ID ' . $giving_id . ': ', $request->all());

        $givingBack = GivingBack::find($giving_id);
        if (!$givingBack) {
            \Log::warning('Giving Back record not found for ID: ' . $giving_id);
            return response()->json(['message' => 'Giving Back record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'givingBack_category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weblink' => 'nullable|url|max:255',
            'image_slider.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Giving Back update ID ' . $giving_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$this->ensureUploadsDirectory()) {
            return response()->json(['error' => 'Unable to access image upload directory.'], 500);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('image_slider')) {
                if ($givingBack->image_slider) {
                    $oldImages = json_decode($givingBack->image_slider, true) ?? [];
                    foreach ($oldImages as $oldImage) {
                        $filePath = public_path($oldImage);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                            \Log::info('Deleted old image: ' . $oldImage);
                        }
                    }
                }
                $imagePaths = [];
                foreach ($request->file('image_slider') as $image) {
                    if ($image->isValid()) {
                        $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('uploads/giving_back_images'), $fileName);
                        $imagePaths[] = 'uploads/giving_back_images/' . $fileName;
                        \Log::info('New image uploaded: uploads/giving_back_images/' . $fileName);
                    }
                }
                $data['image_slider'] = !empty($imagePaths) ? json_encode($imagePaths) : null;
            } else {
                $data['image_slider'] = $givingBack->image_slider;
                \Log::info('No new images uploaded, preserving existing: ' . ($givingBack->image_slider ?: 'none'));
            }

            $givingBack->fill($data)->save();
            \Log::info('Giving Back record updated successfully for ID: ' . $giving_id);
            return response()->json([
                'message' => 'Giving Back record updated successfully.',
                'giving_back' => $givingBack->fresh(),
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error updating giving back record for ID ' . $giving_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update giving back record.', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($giving_id)
    {
        $givingBack = GivingBack::find($giving_id);
        if (!$givingBack) {
            \Log::warning('Giving Back record not found for ID: ' . $giving_id);
            return response()->json(['message' => 'Giving Back record not found'], 404);
        }

        try {
            if ($givingBack->image_slider) {
                $imagePaths = json_decode($givingBack->image_slider, true) ?? [];
                foreach ($imagePaths as $imagePath) {
                    $filePath = public_path($imagePath);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                        \Log::info('Deleted image: ' . $imagePath);
                    }
                }
            }

            $givingBack->delete();
            \Log::info('Giving Back record deleted successfully for ID: ' . $giving_id);
            return response()->json(['message' => 'Giving Back record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting giving back record for ID ' . $giving_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete giving back record.', 'details' => $e->getMessage()], 500);
        }
    }
}