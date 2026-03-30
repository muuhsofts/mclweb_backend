<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class CompanyController extends Controller
{
    /**
     * The path for storing uploaded company images.
     */
    private const IMAGE_UPLOAD_PATH = 'uploads/company_images';

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'homeSliders']);
    }

    /**
     * Display a listing of companies for admin view.
     */
    public function index()
    {
        // Return the collection directly. Laravel handles the JSON response.
        return Company::orderBy('company_id', 'desc')->get();
    }

    /**
     * Display a listing of companies for a public slider.
     */
    public function homeSliders()
    {
        // This endpoint is public via middleware.
        return Company::orderBy('company_id', 'desc')->get();
    }

    /**
     * Fetch the latest company record.
     */
    public function latest()
    {
        // firstOrFail() gets the latest record or throws a ModelNotFoundException (404).
        // This route is protected by the constructor's middleware.
        return Company::orderBy('company_id', 'desc')->firstOrFail();
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['home_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            }

            $company = Company::create($validatedData);

            return response()->json([
                'message' => 'Company created successfully.',
                'company' => $company
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating company: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create company.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified company.
     */
    public function show($company_id)
    {
        // findOrFail automatically handles the 'not found' case.
        return Company::findOrFail($company_id);
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, $company_id)
    {
        // findOrFail ensures the record exists before proceeding.
        $company = Company::findOrFail($company_id);

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'heading' => 'nullable|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            // Handle the file update logic cleanly
            if ($request->hasFile('home_img')) {
                // 1. New file uploaded: Delete old and store new
                if ($company->home_img) {
                    File::delete(public_path($company->home_img));
                }
                $image = $request->file('home_img');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['home_img'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            } elseif (array_key_exists('home_img', $validatedData) && $validatedData['home_img'] === null) {
                // 2. Explicitly removing the file by sending `null`
                if ($company->home_img) {
                    File::delete(public_path($company->home_img));
                }
                $validatedData['home_img'] = null;
            }
            // 3. No file change: The 'home_img' key is not in validatedData, so it won't be updated.

            $company->update($validatedData);

            return response()->json([
                'message' => 'Company updated successfully.',
                'company' => $company->fresh()
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating company ID {$company_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update company.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified company.
     */
    public function destroy($company_id)
    {
        // findOrFail ensures the record exists before attempting deletion.
        $company = Company::findOrFail($company_id);

        try {
            // Delete the associated image file if it exists.
            if ($company->home_img) {
                File::delete(public_path($company->home_img));
            }

            $company->delete();

            // A 204 No Content response is the standard for a successful deletion.
            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting company ID {$company_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete company.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}