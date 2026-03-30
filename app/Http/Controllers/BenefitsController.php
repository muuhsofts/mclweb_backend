<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class BenefitsController extends Controller
{
    /**
     * The path for storing uploaded benefit images.
     */
    private const IMAGE_UPLOAD_PATH = 'uploads/benefits';

    public function __construct()
    {
        // Protect all methods except the public-facing ones.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'allBenefits']);
    }

    /**
     * Display a listing of benefit records.
     */
    public function index()
    {
        // Return the collection directly. Laravel automatically handles JSON conversion.
        return Benefit::orderBy('benefit_id', 'desc')->get();
    }

    /**
     * Display all benefit records (public endpoint).
     */
    public function allBenefits()
    {
        // This method is identical to index() but may be used for a different public route.
        return Benefit::orderBy('benefit_id', 'desc')->get();
    }

    /**
     * Store a newly created benefit record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('img_file')) {
                $image = $request->file('img_file');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['img_file'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            }

            $benefit = Benefit::create($validatedData);

            return response()->json([
                'message' => 'Benefit record created successfully.',
                'benefit' => $benefit
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating benefit record: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create benefit record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified benefit record.
     */
    public function show($benefit_id)
    {
        // findOrFail automatically throws a ModelNotFoundException, which Laravel's
        // handler converts to a 404 response.
        return Benefit::findOrFail($benefit_id);
    }

    /**
     * Update the specified benefit record.
     */
    public function update(Request $request, $benefit_id)
    {
        // findOrFail ensures the record exists before proceeding.
        $benefit = Benefit::findOrFail($benefit_id);

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // `nullable` allows a file or null
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            // Handle the file update logic cleanly
            if ($request->hasFile('img_file')) {
                // 1. New file uploaded: Delete old and store new
                if ($benefit->img_file) {
                    File::delete(public_path($benefit->img_file));
                }
                $image = $request->file('img_file');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['img_file'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            } elseif (array_key_exists('img_file', $validatedData) && $validatedData['img_file'] === null) {
                // 2. Explicitly removing the file by sending `null`
                if ($benefit->img_file) {
                    File::delete(public_path($benefit->img_file));
                }
                $validatedData['img_file'] = null;
            }
            // 3. No file change: The 'img_file' key is not in validatedData, so it won't be updated.

            $benefit->update($validatedData);

            return response()->json([
                'message' => 'Benefit record updated successfully.',
                'benefit' => $benefit->fresh()
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating benefit record ID {$benefit_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update benefit record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified benefit record.
     */
    public function destroy($benefit_id)
    {
        // findOrFail ensures the record exists before attempting to delete.
        $benefit = Benefit::findOrFail($benefit_id);

        try {
            // Delete the associated image file if it exists.
            if ($benefit->img_file) {
                File::delete(public_path($benefit->img_file));
            }

            $benefit->delete();

            // Return a 204 No Content response, which is the standard for a successful deletion.
            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting benefit record ID {$benefit_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete benefit record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}