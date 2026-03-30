<?php

namespace App\Http\Controllers;

use App\Models\OurStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class OurStandardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show','latestOurStandard','allOurStandards']);
    }

    /**
     * Display a listing of our standard records.
     */
    public function allOurStandards()
    {
        try {
            // Verify database connection
            DB::connection()->getPdo();
            
            // Check if table exists
            if (!Schema::hasTable('our_standards')) {
                \Log::error('Table our_standards does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            // Fetch records with explicit fields
            $ourStandardRecords = OurStandard::select('our_id', 'standard_category', 'standard_file', 'weblink', 'description', 'created_at', 'updated_at')
                ->orderBy('our_id', 'desc')
                ->get();

            \Log::info('Successfully fetched our standard records.', ['count' => $ourStandardRecords->count()]);

            return response()->json(['our_standard' => $ourStandardRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching our standard records: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch our standard records.', 'details' => $e->getMessage()], 500);
        }
    }


    public function index()
    {
        try {
            // Verify database connection
            DB::connection()->getPdo();
            
            // Check if table exists
            if (!Schema::hasTable('our_standards')) {
                \Log::error('Table our_standards does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            // Fetch records with explicit fields
            $ourStandardRecords = OurStandard::select('our_id', 'standard_category', 'standard_file', 'weblink', 'description', 'created_at', 'updated_at')
                ->orderBy('our_id', 'desc')
                ->get();

            \Log::info('Successfully fetched our standard records.', ['count' => $ourStandardRecords->count()]);

            return response()->json(['our_standard' => $ourStandardRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching our standard records: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch our standard records.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the latest our standard record based on created_at.
     */
    public function latestOurStandard()
    {
        try {
            // Verify database connection
            DB::connection()->getPdo();

            // Check if table exists
            if (!Schema::hasTable('our_standards')) {
                \Log::error('Table our_standards does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $latestOurStandard = OurStandard::select('our_id', 'standard_category', 'standard_file', 'weblink', 'description', 'created_at', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestOurStandard) {
                \Log::warning('No Our Standard record found for latest request.');
                return response()->json(['message' => 'No Our Standard record found'], 404);
            }

            \Log::info('Successfully fetched latest our standard record.', ['our_id' => $latestOurStandard->our_id]);

            return response()->json(['our_standard' => $latestOurStandard], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest our standard record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch latest our standard record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created our standard record.
     */
    public function store(Request $request)
    {
        \Log::info('Our Standard store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'standard_category' => 'required|string|max:255',
            'standard_file' => 'nullable|file|mimes:pdf,xls,xlsx|max:2048',
            'weblink' => 'nullable|url|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Our Standard store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle standard_file upload (PDF or Excel)
            if ($request->hasFile('standard_file') && $request->file('standard_file')->isValid()) {
                // Ensure the uploads directory exists
                $uploadPath = public_path('uploads');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                    \Log::info('Created uploads directory at: ' . $uploadPath);
                }

                $file = $request->file('standard_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['standard_file'] = 'uploads/' . $fileName;
                \Log::info('File uploaded: ' . $data['standard_file']);
            }

            $ourStandard = OurStandard::create($data);
            \Log::info('Our Standard record created successfully.', ['our_id' => $ourStandard->our_id]);

            return response()->json(['message' => 'Our Standard record created successfully', 'our_standard' => $ourStandard], 201);
        } catch (Exception $e) {
            \Log::error('Error creating our standard record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to create our standard record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified our standard record.
     */
    public function show($our_id)
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('our_standards')) {
                \Log::error('Table our_standards does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $ourStandard = OurStandard::select('our_id', 'standard_category', 'standard_file', 'weblink', 'description', 'created_at', 'updated_at')
                ->find($our_id);

            if (!$ourStandard) {
                \Log::warning('Our Standard record not found for ID: ' . $our_id);
                return response()->json(['message' => 'Our Standard record not found'], 404);
            }

            \Log::info('Successfully fetched our standard record.', ['our_id' => $our_id]);

            return response()->json(['our_standard' => $ourStandard], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching our standard record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch our standard record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified our standard record using POST.
     */
    public function update(Request $request, $our_id)
    {
        \Log::info('Our Standard update request data for ID ' . $our_id . ': ', $request->all());

        try {
            $ourStandard = OurStandard::find($our_id);
            if (!$ourStandard) {
                \Log::warning('Our Standard record not found for ID: ' . $our_id);
                return response()->json(['message' => 'Our Standard record not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'standard_category' => 'required|string|max:255',
                'standard_file' => 'nullable|file|mimes:pdf,xls,xlsx|max:2048',
                'weblink' => 'nullable|url|max:255',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation failed for Our Standard update ID ' . $our_id . ': ', $validator->errors()->toArray());
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();
            \Log::info('Validated data for update ID ' . $our_id . ': ', $data);

            // Handle standard_file upload (PDF or Excel)
            if ($request->hasFile('standard_file') && $request->file('standard_file')->isValid()) {
                // Delete old file if it exists
                if ($ourStandard->standard_file && File::exists(public_path($ourStandard->standard_file))) {
                    File::delete(public_path($ourStandard->standard_file));
                    \Log::info('Deleted old file: ' . $ourStandard->standard_file);
                }

                // Ensure the uploads directory exists
                $uploadPath = public_path('uploads');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                    \Log::info('Created uploads directory at: ' . $uploadPath);
                }

                $file = $request->file('standard_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['standard_file'] = 'uploads/' . $fileName;
                \Log::info('New file uploaded: ' . $data['standard_file']);
            } else {
                $data['standard_file'] = $ourStandard->standard_file;
                \Log::info('No new file uploaded, preserving existing: ' . ($ourStandard->standard_file ?: 'none'));
            }

            $ourStandard->fill($data)->save();
            \Log::info('Our Standard record updated successfully for ID: ' . $our_id);

            return response()->json([
                'message' => 'Our Standard record updated successfully.',
                'our_standard' => $ourStandard->fresh()
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error updating our standard record for ID ' . $our_id . ': ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to update our standard record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified our standard record.
     */
    public function destroy($our_id)
    {
        try {
            $ourStandard = OurStandard::find($our_id);
            if (!$ourStandard) {
                \Log::warning('Our Standard record not found for ID: ' . $our_id);
                return response()->json(['message' => 'Our Standard record not found'], 404);
            }

            // Delete standard_file if it exists
            if ($ourStandard->standard_file && File::exists(public_path($ourStandard->standard_file))) {
                File::delete(public_path($ourStandard->standard_file));
                \Log::info('Deleted file: ' . $ourStandard->standard_file);
            }

            $ourStandard->delete();
            \Log::info('Our Standard record deleted successfully for ID: ' . $our_id);

            return response()->json(['message' => 'Our Standard record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting our standard record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to delete our standard record.', 'details' => $e->getMessage()], 500);
        }
    }
}
