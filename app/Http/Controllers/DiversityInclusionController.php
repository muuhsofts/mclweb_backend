<?php

namespace App\Http\Controllers;

use App\Models\DiversityInclusion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DiversityInclusionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestdiversityinclusion', 'allDiversitiesAndIclusion']);
    }

    /**
     * Display a listing of diversity inclusion records.
     */
    public function index()
    {
        try {
            $diversityRecords = DiversityInclusion::orderBy('diversity_id', 'desc')->get();
            return response()->json(['diversity' => $diversityRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching diversity records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch diversity records.'], 500);
        }
    }

    /**
     * Display all diversity inclusion records.
     */
    public function allDiversitiesAndIclusion()
    {
        try {
            $diversityRecords = DiversityInclusion::orderBy('diversity_id', 'desc')->get();
            return response()->json(['diversity' => $diversityRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching diversity records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch diversity records.'], 500);
        }
    }

    /**
     * Display the latest diversity inclusion record based on created_at.
     */
    public function latestdiversityinclusion()
    {
        try {
            $latestDiversity = DiversityInclusion::orderBy('created_at', 'desc')->first();
            
            if (!$latestDiversity) {
                return response()->json(['message' => 'No Diversity record found'], 404);
            }

            return response()->json(['diversity' => $latestDiversity], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest diversity record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest diversity record.'], 500);
        }
    }

    /**
     * Store a newly created diversity inclusion record.
     */
    public function store(Request $request)
    {
        \Log::info('Diversity store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'home_page' => 'nullable|string|max:255',
            'diversity_category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Diversity store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle pdf_file upload
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                $file = $request->file('pdf_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $uploadPath = public_path('uploads');
                
                // Ensure uploads directory exists
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Move file to public/uploads
                $file->move($uploadPath, $filename);
                $data['pdf_file'] = 'uploads/' . $filename;
                \Log::info('PDF file uploaded: ' . $data['pdf_file']);
            }

            $diversity = DiversityInclusion::create($data);
            return response()->json(['message' => 'Diversity record created successfully', 'diversity' => $diversity], 201);
        } catch (Exception $e) {
            \Log::error('Error creating diversity record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create diversity record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified diversity inclusion record.
     */
    public function show($diversity_id)
    {
        $diversity = DiversityInclusion::find($diversity_id);

        if (!$diversity) {
            return response()->json(['message' => 'Diversity record not found'], 404);
        }

        return response()->json(['diversity' => $diversity], 200);
    }

    /**
     * Update the specified diversity inclusion record using POST.
     */
    public function update(Request $request, $diversity_id)
    {
        \Log::info('Diversity update request data for ID ' . $diversity_id . ': ', $request->all());

        $diversity = DiversityInclusion::find($diversity_id);
        if (!$diversity) {
            \Log::warning('Diversity record not found for ID: ' . $diversity_id);
            return response()->json(['message' => 'Diversity record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'home_page' => 'nullable|string|max:255',
            'diversity_category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Diversity update ID ' . $diversity_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            \Log::info('Validated data for update ID ' . $diversity_id . ': ', $data);

            // Handle pdf_file upload
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                // Delete old pdf_file if it exists
                if ($diversity->pdf_file && file_exists(public_path($diversity->pdf_file))) {
                    unlink(public_path($diversity->pdf_file));
                    \Log::info('Deleted old PDF file: ' . $diversity->pdf_file);
                }

                $file = $request->file('pdf_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $uploadPath = public_path('uploads');

                // Ensure uploads directory exists
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Move file to public/uploads
                $file->move($uploadPath, $filename);
                $data['pdf_file'] = 'uploads/' . $filename;
                \Log::info('New PDF file uploaded: ' . $data['pdf_file']);
            } else {
                $data['pdf_file'] = $diversity->pdf_file;
                \Log::info('No new PDF file uploaded, preserving existing: ' . ($diversity->pdf_file ?: 'none'));
            }

            // Update the record
            $diversity->fill($data)->save();
            \Log::info('Diversity record updated successfully for ID: ' . $diversity_id);

            return response()->json([
                'message' => 'Diversity record updated successfully.',
                'diversity' => $diversity->fresh()
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error updating diversity record for ID ' . $diversity_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update diversity record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified diversity inclusion record.
     */
    public function destroy($diversity_id)
    {
        $diversity = DiversityInclusion::find($diversity_id);
        if (!$diversity) {
            return response()->json(['message' => 'Diversity record not found'], 404);
        }

        try {
            // Delete pdf_file if it exists
            if ($diversity->pdf_file && file_exists(public_path($diversity->pdf_file))) {
                unlink(public_path($diversity->pdf_file));
                \Log::info('Deleted PDF file: ' . $diversity->pdf_file);
            }

            $diversity->delete();
            return response()->json(['message' => 'Diversity record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting diversity record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete diversity record.', 'details' => $e->getMessage()], 500);
        }
    }
}