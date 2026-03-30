<?php

namespace App\Http\Controllers;

use App\Models\OurStandardHome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class OurStandardHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latest','ourStandardHomeSlider']);
    }

    private function jsonResponse($data, string $message, int $status)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function handleImageUpload(Request $request, ?OurStandardHome $ourStandardHome = null): ?string
    {
        if (!$request->hasFile('home_img') || !$request->file('home_img')->isValid()) {
            return $ourStandardHome?->home_img;
        }

        // Delete old image if updating and one exists
        if ($ourStandardHome && $ourStandardHome->home_img && file_exists(public_path($ourStandardHome->home_img))) {
            unlink(public_path($ourStandardHome->home_img));
            Log::info('Deleted old image file', ['path' => $ourStandardHome->home_img]);
        }

        $image = $request->file('home_img');
        $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
        $uploadPath = 'Uploads/our_standard_home_images';
        $image->move(public_path($uploadPath), $imageName);
        $imagePath = $uploadPath . '/' . $imageName;

        Log::info('Uploaded new image file', ['path' => $imagePath]);
        return $imagePath;
    }


    public function ourStandardHomeSlider()
    {
        try {
            $ourStandards = OurStandardHome::orderBy('id', 'desc')->get();
            return $this->jsonResponse(['our_standard_homes' => $ourStandards], 'OurStandardHome records retrieved successfully', 200);
        } catch (Exception $e) {
            Log::error('Error fetching OurStandardHome records', ['error' => $e->getMessage()]);
            return $this->jsonResponse(null, 'Failed to fetch OurStandardHome records', 500);
        }
    }

    public function index()
    {
        try {
            $ourStandards = OurStandardHome::orderBy('created_at', 'desc')->get();
            return $this->jsonResponse(['our_standard_homes' => $ourStandards], 'Records retrieved successfully', 200);
        } catch (Exception $e) {
            Log::error('Error fetching OurStandardHome records', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch records', 'error' => $e->getMessage()], 500);
        }
    }

    public function latest()
    {
        try {
            $latest = OurStandardHome::latest('created_at')->first();
            if (!$latest) {
                return $this->jsonResponse(null, 'No record found', 404);
            }
            return $this->jsonResponse(['our_standard_home' => $latest], 'Latest record retrieved successfully', 200);
        } catch (Exception $e) {
            Log::error('Error fetching latest OurStandardHome record', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch latest record', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string',
            'home_img' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            $data['home_img'] = $this->handleImageUpload($request);
            $record = OurStandardHome::create($data);
            return $this->jsonResponse(['our_standard_home' => $record], 'Record created successfully', 201);
        } catch (Exception $e) {
            Log::error('Error creating OurStandardHome record', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create record', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $record = OurStandardHome::find($id);
            if (!$record) {
                return $this->jsonResponse(null, 'Record not found', 404);
            }
            return $this->jsonResponse(['our_standard_home' => $record], 'Record retrieved successfully', 200);
        } catch (Exception $e) {
            Log::error('Error showing OurStandardHome record', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to retrieve record', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $record = OurStandardHome::find($id);
        if (!$record) {
            return $this->jsonResponse(null, 'Record not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'heading' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'home_img' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            $data['home_img'] = $this->handleImageUpload($request, $record);
            $record->update($data);
            return $this->jsonResponse(['our_standard_home' => $record->fresh()], 'Record updated successfully', 200);
        } catch (Exception $e) {
            Log::error('Error updating OurStandardHome record', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update record', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $record = OurStandardHome::find($id);
            if (!$record) {
                return $this->jsonResponse(null, 'Record not found', 404);
            }
            // Delete associated image file
            if ($record->home_img && file_exists(public_path($record->home_img))) {
                unlink(public_path($record->home_img));
            }
            $record->delete();
            return $this->jsonResponse(null, 'Record deleted successfully', 200);
        } catch (Exception $e) {
            Log::error('Error deleting OurStandardHome record', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete record', 'error' => $e->getMessage()], 500);
        }
    }
}