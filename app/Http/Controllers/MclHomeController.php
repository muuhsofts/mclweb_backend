<?php

namespace App\Http\Controllers;

use App\Models\MclHome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Http\JsonResponse;

class MclHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'mclhmeSlider']);
    }

    public function index(): JsonResponse
    {
        try {
            $sliders = MclHome::orderBy('mcl_home_id', 'desc')->get();
            return response()->json(['data' => $sliders], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch home sliders'], 500);
        }
    }

    public function mclhmeSlider(): JsonResponse
    {
        return $this->index();
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'mcl_home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('mcl_home_img')) {
                $image = $request->file('mcl_home_img');
                $imageName = time() . '_' . str_replace([' ', ':'], '_', $image->getClientOriginalName());
                $destinationPath = public_path('Uploads/home_sliders');
                File::makeDirectory($destinationPath, 0755, true, true);
                $image->move($destinationPath, $imageName);
                $data['mcl_home_img'] = 'Uploads/home_sliders/' . $imageName;
            }

            $slider = MclHome::create($data);
            return response()->json(['message' => 'Home slider created successfully', 'data' => $slider], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create home slider'], 500);
        }
    }

    public function show($mcl_home_id): JsonResponse
    {
        try {
            $slider = MclHome::findOrFail($mcl_home_id);
            return response()->json(['data' => $slider], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Home slider not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch home slider'], 500);
        }
    }

    public function update(Request $request, $mcl_home_id): JsonResponse
    {
        try {
            $slider = MclHome::findOrFail($mcl_home_id);
            $validator = Validator::make($request->all(), [
                'heading' => 'required|string|max:255',
                'mcl_home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('mcl_home_img')) {
                if ($slider->mcl_home_img && File::exists(public_path($slider->mcl_home_img))) {
                    File::delete(public_path($slider->mcl_home_img));
                }
                $image = $request->file('mcl_home_img');
                $imageName = time() . '_' . str_replace([' ', ':'], '_', $image->getClientOriginalName());
                $destinationPath = public_path('Uploads/home_sliders');
                File::makeDirectory($destinationPath, 0755, true, true);
                $image->move($destinationPath, $imageName);
                $data['mcl_home_img'] = 'Uploads/home_sliders/' . $imageName;
            } elseif ($request->has('mcl_home_img') && $request->input('mcl_home_img') === null) {
                if ($slider->mcl_home_img && File::exists(public_path($slider->mcl_home_img))) {
                    File::delete(public_path($slider->mcl_home_img));
                }
                $data['mcl_home_img'] = null;
            }

            $slider->update($data);
            return response()->json(['message' => 'Home slider updated successfully', 'data' => $slider->fresh()], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Home slider not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error updating home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update home slider'], 500);
        }
    }

    public function destroy($mcl_home_id): JsonResponse
    {
        try {
            $slider = MclHome::findOrFail($mcl_home_id);
            if ($slider->mcl_home_img && File::exists(public_path($slider->mcl_home_img))) {
                File::delete(public_path($slider->mcl_home_img));
            }
            $slider->delete();
            return response()->json(['message' => 'Home slider deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Home slider not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete home slider'], 500);
        }
    }
}