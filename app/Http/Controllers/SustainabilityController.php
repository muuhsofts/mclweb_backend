<?php

namespace App\Http\Controllers;

use App\Models\Sustainability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class SustainabilityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show','latestSustainability','allSustainability']);
    }

    /**
     * Display a listing of sustainability records.
     */
    public function index()
    {
        try {
            $sustainabilityRecords = Sustainability::orderBy('sustain_id', 'desc')->get();
            return response()->json(['data' => $sustainabilityRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching sustainability records: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Failed to fetch sustainability records.'], 500);
        }
    }

    public function allSustainability()
    {
        try {
            $sustainabilityRecords = Sustainability::orderBy('sustain_id', 'desc')->get();
            return response()->json(['data' => $sustainabilityRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching sustainability records: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Failed to fetch sustainability records.'], 500);
        }
    }

    /**
     * Display the latest sustainability record based on created_at.
     */
    public function latestSustainability()
    {
        try {
            $latestSustainability = Sustainability::orderBy('created_at', 'desc')->first();
            if (!$latestSustainability) {
                return response()->json(['message' => 'No sustainability record found'], 404);
            }
            return response()->json(['data' => $latestSustainability], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest sustainability record: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Failed to fetch latest sustainability record.'], 500);
        }
    }

    /**
     * Handle file upload and return the stored path.
     */
    private function handleFileUpload($file, string $fieldName, ?string $existingPath = null): ?string
    {
        if (!$file || !$file->isValid()) {
            return $existingPath;
        }

        // Define upload path
        $uploadPath = public_path('uploads');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
            \Log::info("Created directory: {$uploadPath}");
        }

        // Delete existing file if it exists
        if ($existingPath && file_exists(public_path($existingPath))) {
            unlink(public_path($existingPath));
            \Log::info("Deleted existing {$fieldName}: {$existingPath}");
        }

        // Store the new file
        $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $file->move($uploadPath, $fileName);
        $path = 'uploads/' . $fileName;
        \Log::info("Uploaded new {$fieldName}: {$path}");

        return $path;
    }

    /**
     * Store a newly created sustainability record.
     */
    public function store(Request $request)
    {
        \Log::info('Sustainability store request received', ['data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'sustain_category' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weblink' => 'nullable|url|max:255',
            'sustain_pdf_file' => 'nullable|file|mimes:pdf|max:2048',
            'sustain_image_file' => 'nullable|file|mimes:png,jpeg,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for sustainability store', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request, $validator) {
                $data = $validator->validated();

                // Handle file uploads
                $data['sustain_pdf_file'] = $this->handleFileUpload(
                    $request->file('sustain_pdf_file'),
                    'PDF file'
                );
                $data['sustain_image_file'] = $this->handleFileUpload(
                    $request->file('sustain_image_file'),
                    'image file'
                );

                $sustainability = Sustainability::create($data);
                return response()->json([
                    'message' => 'Sustainability record created successfully',
                    'sustainability' => $sustainability
                ], 201);
            });
        } catch (Exception $e) {
            \Log::error('Error creating sustainability record: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Failed to create sustainability record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sustainability record.
     */
    public function show($sustain_id)
    {
        $sustainability = Sustainability::find($sustain_id);
        if (!$sustainability) {
            \Log::warning('Sustainability record not found', ['sustain_id' => $sustain_id]);
            return response()->json(['message' => 'Sustainability record not found'], 404);
        }
        return response()->json(['data' => $sustainability], 200);
    }

    /**
     * Update the specified sustainability record.
     */
    public function update(Request $request, $sustain_id)
    {
        \Log::info('Sustainability update request received', ['sustain_id' => $sustain_id, 'data' => $request->all()]);

        $sustainability = Sustainability::find($sustain_id);
        if (!$sustainability) {
            \Log::warning('Sustainability record not found', ['sustain_id' => $sustain_id]);
            return response()->json(['message' => 'Sustainability record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sustain_category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weblink' => 'nullable|url|max:255',
            'sustain_pdf_file' => 'nullable|file|mimes:pdf|max:2048',
            'sustain_image_file' => 'nullable|file|mimes:png,jpeg,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for sustainability update', ['sustain_id' => $sustain_id, 'errors' => $validator->errors()->toArray()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request, $validator, $sustainability) {
                $data = $validator->validated();

                // Handle file uploads
                $data['sustain_pdf_file'] = $this->handleFileUpload(
                    $request->file('sustain_pdf_file'),
                    'PDF file',
                    $sustainability->sustain_pdf_file
                );
                $data['sustain_image_file'] = $this->handleFileUpload(
                    $request->file('sustain_image_file'),
                    'image file',
                    $sustainability->sustain_image_file
                );

                $sustainability->fill($data)->save();
                \Log::info('Sustainability record updated successfully', ['sustain_id' => $sustainability->sustain_id]);
                return response()->json([
                    'message' => 'Sustainability record updated successfully.',
                    'sustainability' => $sustainability->fresh()
                ], 200);
            });
        } catch (Exception $e) {
            \Log::error('Error updating sustainability record: ' . $e->getMessage(), ['sustain_id' => $sustain_id, 'exception' => $e]);
            return response()->json(['error' => 'Failed to update sustainability record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified sustainability record.
     */
    public function destroy($sustain_id)
    {
        $sustainability = Sustainability::find($sustain_id);
        if (!$sustainability) {
            \Log::warning('Sustainability record not found', ['sustain_id' => $sustain_id]);
            return response()->json(['message' => 'Sustainability record not found'], 404);
        }

        try {
            return DB::transaction(function () use ($sustainability, $sustain_id) {
                // Delete associated files
                foreach (['sustain_pdf_file', 'sustain_image_file'] as $field) {
                    if ($sustainability->$field && file_exists(public_path($sustainability->$field))) {
                        unlink(public_path($sustainability->$field));
                        \Log::info("Deleted {$field}: {$sustainability->$field}");
                    }
                }

                $sustainability->delete();
                \Log::info('Sustainability record deleted successfully', ['sustain_id' => $sustain_id]);
                return response()->json(['message' => 'Sustainability record deleted successfully'], 200);
            });
        } catch (Exception $e) {
            \Log::error('Error deleting sustainability record: ' . $e->getMessage(), ['sustain_id' => $sustain_id, 'exception' => $e]);
            return response()->json(['error' => 'Failed to delete sustainability record.', 'details' => $e->getMessage()], 500);
        }
    }
}