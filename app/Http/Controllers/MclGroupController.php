<?php

namespace App\Http\Controllers;

use App\Models\MclGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Http\JsonResponse;

class MclGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latest', 'allMclgroup','allMclGroups']);
    }

    public function index(): JsonResponse
    {
        try {
            $mclGroups = MclGroup::orderBy('mcl_id', 'desc')->get();
            return response()->json(['data' => $mclGroups], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching mcl_groups: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch mcl_groups'], 500);
        }
    }

    /**
     * Count the total number of MCL group records.
     */
    public function countMclGroups(): JsonResponse
    {
        try {
            $count = MclGroup::count();
            return response()->json(['count_mcl_group' => $count], 200);
        } catch (\Exception $e) {
            \Log::error('Error counting MCL groups: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count MCL groups'], 500);
        }
    }

    public function allMclGroups(): JsonResponse
    {
        try {
            $mclGroups = MclGroup::orderBy('mcl_id', 'desc')->get();
            return response()->json(['data' => $mclGroups], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching mcl_groups: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch mcl_groups'], 500);
        }
    }

    

    public function allMclgroup(): JsonResponse
    {
        return $this->index();
    }



    public function latest(): JsonResponse
{
    try {
        $latestMclGroup = MclGroup::orderBy('created_at', 'desc')->first();
        if (!$latestMclGroup) {
            return response()->json(['message' => 'No MclGroup found'], 404);
        }
        return response()->json(['data' => $latestMclGroup], 200);
    } catch (\Exception $e) {
        \Log::error('Error fetching latest mcl_group: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch latest mcl_group'], 500);
    }
}

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mcl_category' => 'required|string|max:255',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
            'weblink' => 'nullable|string|max:255',
            'home_page' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('image_file')) {
                $image = $request->file('image_file');
                $imageName = time() . '_' . str_replace([' ', ':'], '_', $image->getClientOriginalName());
                $destinationPath = public_path('Uploads/mcl_images');
                File::makeDirectory($destinationPath, 0755, true, true);
                $image->move($destinationPath, $imageName);
                $data['image_file'] = 'Uploads/mcl_images/' . $imageName;
            }

            $mclGroup = MclGroup::create($data);
            return response()->json(['message' => 'MclGroup created successfully', 'data' => $mclGroup], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating mcl_group: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create mcl_group'], 500);
        }
    }

    public function show($mcl_id): JsonResponse
    {
        try {
            $mclGroup = MclGroup::findOrFail($mcl_id);
            return response()->json(['data' => $mclGroup], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'MclGroup not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching mcl_group: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch mcl_group'], 500);
        }
    }

    public function update(Request $request, $mcl_id): JsonResponse
    {
        try {
            $mclGroup = MclGroup::findOrFail($mcl_id);
            $validator = Validator::make($request->all(), [
                'mcl_category' => 'required|string|max:255',
                'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'nullable|string',
                'weblink' => 'nullable|string|max:255',
                'home_page' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('image_file')) {
                if ($mclGroup->image_file && File::exists(public_path($mclGroup->image_file))) {
                    File::delete(public_path($mclGroup->image_file));
                }
                $image = $request->file('image_file');
                $imageName = time() . '_' . str_replace([' ', ':'], '_', $image->getClientOriginalName());
                $destinationPath = public_path('Uploads/mcl_images');
                File::makeDirectory($destinationPath, 0755, true, true);
                $image->move($destinationPath, $imageName);
                $data['image_file'] = 'Uploads/mcl_images/' . $imageName;
            } elseif ($request->has('image_file') && $request->input('image_file') === null) {
                if ($mclGroup->image_file && File::exists(public_path($mclGroup->image_file))) {
                    File::delete(public_path($mclGroup->image_file));
                }
                $data['image_file'] = null;
            }

            $mclGroup->update($data);
            return response()->json(['message' => 'MclGroup updated successfully', 'data' => $mclGroup->fresh()], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'MclGroup not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error updating mcl_group: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update mcl_group'], 500);
        }
    }

    public function destroy($mcl_id): JsonResponse
    {
        try {
            $mclGroup = MclGroup::findOrFail($mcl_id);
            if ($mclGroup->image_file && File::exists(public_path($mclGroup->image_file))) {
                File::delete(public_path($mclGroup->image_file));
            }
            $mclGroup->delete();
            return response()->json(['message' => 'MclGroup deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'MclGroup not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting mcl_group: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete mcl_group'], 500);
        }
    }
}