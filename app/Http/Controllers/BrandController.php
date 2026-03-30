<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Exception;

class BrandController extends Controller
{
    /**
     * The path for storing uploaded brand images.
     * Note: Sticking with 'Uploads' to match the original controller. Convention is usually lowercase.
     */
    private const IMAGE_UPLOAD_PATH = 'Uploads/brands';

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'allBrands', 'latestbrand']);
    }

    /**
     * Display a listing of brands.
     */
    public function index()
    {
        // Return the collection directly. Laravel handles the JSON response.
        return Brand::orderBy('brand_id', 'desc')->get();
    }

     /**
     * Display all brands (public endpoint).
     */
    public function allBrands(): JsonResponse
    {
        $brands = Brand::orderBy('brand_id', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'All brands retrieved successfully',
            'data' => $brands
        ], 200);
    }

    /**
     * Fetch the latest brand.
     */
    public function latestbrand()
    {
        // firstOrFail() gets the latest record or throws a ModelNotFoundException (404).
        return Brand::latest('brand_id')->firstOrFail();
    }

    /**
     * Store a newly created brand.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'url_link' => 'nullable|url|max:2048',
            'brand_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('brand_img')) {
                $image = $request->file('brand_img');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['brand_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            }

            $brand = Brand::create($validatedData);

            return response()->json([
                'message' => 'Brand created successfully.',
                'brand' => $brand
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating brand: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create brand.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified brand.
     */
    public function show($brand_id)
    {
        // findOrFail automatically handles the 'not found' case.
        return Brand::findOrFail($brand_id);
    }

    /**
     * Update the specified brand.
     */
    public function update(Request $request, $brand_id)
    {
        // findOrFail ensures the record exists before proceeding.
        $brand = Brand::findOrFail($brand_id);

        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'url_link' => 'nullable|url|max:2048',
            'brand_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            // Handle the file update logic cleanly
            if ($request->hasFile('brand_img')) {
                // 1. New file uploaded: Delete old and store new
                if ($brand->brand_img) {
                    File::delete(public_path($brand->brand_img));
                }
                $image = $request->file('brand_img');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['brand_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            } elseif (array_key_exists('brand_img', $validatedData) && $validatedData['brand_img'] === null) {
                // 2. Explicitly removing the file by sending `null`
                if ($brand->brand_img) {
                    File::delete(public_path($brand->brand_img));
                }
                $validatedData['brand_img'] = null;
            }
            // 3. No file change: The 'brand_img' key is not in validatedData, so it won't be updated.

            $brand->update($validatedData);

            return response()->json([
                'message' => 'Brand updated successfully.',
                'brand' => $brand->fresh()
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating brand ID {$brand_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update brand.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified brand.  hello mudhihir nyemayurtuttrrt4r4
     */
    public function destroy($brand_id)
    {
        // findOrFail ensures the record exists before attempting deletion.
        $brand = Brand::findOrFail($brand_id);

        try {
            // Delete the associated image file if it exists.
            if ($brand->brand_img) {
                File::delete(public_path($brand->brand_img));
            }

            $brand->delete();

            // Return a 204 No Content response, which is the standard for a successful deletion.
            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting brand ID {$brand_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete brand.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}