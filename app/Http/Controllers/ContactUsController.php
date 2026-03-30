<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class ContactUsController extends Controller
{
    /**
     * The path for storing uploaded contact images.
     * Note: Sticking with 'Uploads' to match the original controller. Convention is usually lowercase.
     */
    private const IMAGE_UPLOAD_PATH = 'Uploads/contact_images';

    public function __construct()
    {
        // Protect all methods except the public-facing ones.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'allContactUs', 'contactDropDown']);
    }

    /**
     * Display all contact records for public view.
     */
    public function allContactUs()
    {
        // Return the collection directly.
        return ContactUs::orderBy('contactus_id', 'desc')->get();
    }

    /**
     * Display a listing of contact records for admin view.
     */
    public function index()
    {
        // Return the collection directly.
        return ContactUs::orderBy('contactus_id', 'desc')->get();
    }

    /**
     * Provide a list of contacts for a dropdown menu (ID and category).
     */
    public function contactDropDown()
    {
        // Return the selected fields directly as a collection.
        return ContactUs::select('contactus_id', 'category')
            ->orderBy('contactus_id', 'desc')
            ->get();
    }


    /**
     * Store a newly created contact record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'url_link' => 'nullable|url|max:255',
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

            $contact = ContactUs::create($validatedData);

            return response()->json([
                'message' => 'Contact record created successfully.',
                'contact' => $contact
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating contact record: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create contact record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified contact record.
     */
    public function show($contactus_id)
    {
        // findOrFail automatically handles the 'not found' case.
        return ContactUs::findOrFail($contactus_id);
    }

    /**
     * Update the specified contact record.
     */
    public function update(Request $request, $contactus_id)
    {
        // findOrFail ensures the record exists before proceeding.
        $contact = ContactUs::findOrFail($contactus_id);

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'url_link' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            // Handle the file update logic cleanly
            if ($request->hasFile('img_file')) {
                // 1. New file uploaded: Delete old and store new
                if ($contact->img_file) {
                    File::delete(public_path($contact->img_file));
                }
                $image = $request->file('img_file');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path(self::IMAGE_UPLOAD_PATH), $imageName);
                $validatedData['img_file'] = self::IMAGE_UPLOAD_PATH . '/' . $imageName;
            } elseif (array_key_exists('img_file', $validatedData) && $validatedData['img_file'] === null) {
                // 2. Explicitly removing the file by sending `null`
                if ($contact->img_file) {
                    File::delete(public_path($contact->img_file));
                }
                $validatedData['img_file'] = null;
            }
            // 3. No file change: The 'img_file' key is not in validatedData, so it won't be updated.

            $contact->update($validatedData);

            return response()->json([
                'message' => 'Contact record updated successfully.',
                'contact' => $contact->fresh()
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating contact record ID {$contactus_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update contact record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified contact record.
     */
    public function destroy($contactus_id)
    {
        // findOrFail ensures the record exists before attempting deletion.
        $contact = ContactUs::findOrFail($contactus_id);

        try {
            // Delete the associated image file if it exists.
            if ($contact->img_file) {
                File::delete(public_path($contact->img_file));
            }

            $contact->delete();

            // Return a 204 No Content response, which is the standard for a successful deletion.
            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting contact record ID {$contactus_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete contact record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
}