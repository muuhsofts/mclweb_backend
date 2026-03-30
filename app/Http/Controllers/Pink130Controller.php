<?php

namespace App\Http\Controllers;

use App\Models\Pink130;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class Pink130Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show','allMCLpink']);
    }

    /**
     * Display a listing of pink-130 records.
     */
    public function index()
    {
        try {
            $pink130s = Pink130::orderBy('pink_id', 'desc')->get();
            return response()->json(['pink130s' => $pink130s], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching pink-130 records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch pink-130 records.'], 500);
        }
    }

     public function allMCLpink()
    {
        try {
            $pink130s = Pink130::orderBy('pink_id', 'desc')->get();
            return response()->json(['pink130s' => $pink130s], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching pink-130 records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch pink-130 records.'], 500);
        }
    }

    /**
     * Display the latest pink-130 record based on created_at.
     */
    public function latestMclPink130()
    {
        try {
            $latestPink130 = Pink130::orderBy('created_at', 'desc')->first();
            
            if (!$latestPink130) {
                return response()->json(['message' => 'No Pink130 record found'], 404);
            }

            return response()->json(['pink130' => $latestPink130], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest pink-130 record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest pink-130 record.'], 500);
        }
    }

    /**
     * Store a newly created pink-130 record.
     */
    public function store(Request $request)
    {
        \Log::info('Pink130 store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'video' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Pink130 store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle pdf_file upload
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                $pdf = $request->file('pdf_file');
                $pdfName = time() . '_' . preg_replace('/\s+/', '_', $pdf->getClientOriginalName());
                $uploadPath = public_path('uploads/pink130_pdfs');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $pdf->move($uploadPath, $pdfName);
                $data['pdf_file'] = 'uploads/pink130_pdfs/' . $pdfName;
                \Log::info('PDF file uploaded: ' . $data['pdf_file']);
            }

            $pink130 = Pink130::create($data);
            return response()->json(['message' => 'Pink130 record created successfully', 'pink130' => $pink130], 201);
        } catch (Exception $e) {
            \Log::error('Error creating pink-130 record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create pink-130 record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified pink-130 record.
     */
    public function show($pink_id)
    {
        $pink130 = Pink130::find($pink_id);

        if (!$pink130) {
            return response()->json(['message' => 'Pink130 record not found'], 404);
        }

        return response()->json(['pink130' => $pink130], 200);
    }

    /**
     * Update the specified pink-130 record using POST.
     */
    public function update(Request $request, $pink_id)
    {
        \Log::info('Pink130 update request data: ', $request->all());

        $pink130 = Pink130::find($pink_id);
        if (!$pink130) {
            return response()->json(['message' => 'Pink130 record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'video' => 'nullable|string|url|max:255',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Pink130 update: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle pdf_file upload
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                // Delete old pdf_file if it exists
                if ($pink130->pdf_file && file_exists(public_path($pink130->pdf_file))) {
                    unlink(public_path($pink130->pdf_file));
                    \Log::info('Deleted old PDF file: ' . $pink130->pdf_file);
                }

                $pdf = $request->file('pdf_file');
                $pdfName = time() . '_' . preg_replace('/\s+/', '_', $pdf->getClientOriginalName());
                $uploadPath = public_path('uploads/pink130_pdfs');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $pdf->move($uploadPath, $pdfName);
                $data['pdf_file'] = 'uploads/pink130_pdfs/' . $pdfName;
                \Log::info('New PDF file uploaded: ' . $data['pdf_file']);
            } else {
                $data['pdf_file'] = $pink130->pdf_file;
                \Log::info('No new PDF file uploaded, preserving existing: ' . ($pink130->pdf_file ?: 'none'));
            }

            $pink130->fill($data)->save();
            return response()->json(['message' => 'Pink130 record updated successfully.', 'pink130' => $pink130->fresh()], 200);
        } catch (Exception $e) {
            \Log::error('Error updating pink-130 record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update pink-130 record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified pink-130 record.
     */
    public function destroy($pink_id)
    {
        $pink130 = Pink130::find($pink_id);
        if (!$pink130) {
            return response()->json(['message' => 'Pink130 record not found'], 404);
        }

        try {
            // Delete pdf_file if it exists
            if ($pink130->pdf_file && file_exists(public_path($pink130->pdf_file))) {
                unlink(public_path($pink130->pdf_file));
                \Log::info('Deleted PDF file: ' . $pink130->pdf_file);
            }

            $pink130->delete();
            return response()->json(['message' => 'Pink130 record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting pink-130 record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete pink-130 record.', 'details' => $e->getMessage()], 500);
        }
    }
}