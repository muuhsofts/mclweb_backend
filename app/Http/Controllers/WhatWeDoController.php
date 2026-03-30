<?php

namespace App\Http\Controllers;

use App\Models\WhatWeDo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class WhatWeDoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'allRecords']);
    }

    /**
     * Display a listing of what we do records.
     */
    public function allRecords()
    {
        try {
            // Verify database connection
            DB::connection()->getPdo();
            
            // Check if table exists
            if (!Schema::hasTable('tbl_what_we_do')) {
                \Log::error('Table tbl_what_we_do does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            // Fetch records with explicit fields
            $records = WhatWeDo::select('what_we_do_id', 'category', 'description', 'img_file', 'created_at', 'updated_at')
                ->orderBy('what_we_do_id', 'desc')
                ->get();

            \Log::info('Successfully fetched what we do records.', ['count' => $records->count()]);

            return response()->json(['records' => $records], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching what we do records: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch what we do records.', 'details' => $e->getMessage()], 500);
        }
    }


    public function fetchAllCategories()
{
    try {
        // Check if table exists
        if (!Schema::hasTable('tbl_what_we_do')) {
            \Log::error('Table tbl_what_we_do does not exist.');
            return response()->json(['error' => 'Database table not found.'], 500);
        }

        // Get all categories
        $categories = WhatWeDo::select('what_we_do_id', 'category')
            ->orderBy('category', 'asc')
            ->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories found.'], 404);
        }

        return response()->json(['categories' => $categories], 200);
    } catch (\Exception $e) {
        \Log::error('Error fetching categories: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch categories.'], 500);
    }
}


    /**
     * Display a listing of what we do records (alias for allRecords).
     */
    public function index()
    {
        return $this->allRecords();
    }

    /**
     * Store a newly created what we do record.
     */
    public function store(Request $request)
    {
        \Log::info('What we do store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'img_file' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for what we do store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle img_file upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                $uploadPath = public_path('uploads/whatwedo');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                    \Log::info('Created uploads/whatwedo directory at: ' . $uploadPath);
                }

                $file = $request->file('img_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['img_file'] = 'uploads/whatwedo/' . $fileName;
                \Log::info('Image uploaded: ' . $data['img_file']);
            }

            $record = WhatWeDo::create($data);
            \Log::info('What we do record created successfully.', ['what_we_do_id' => $record->what_we_do_id]);

            return response()->json(['message' => 'What we do record created successfully', 'record' => $record], 201);
        } catch (Exception $e) {
            \Log::error('Error creating what we do record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to create what we do record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified what we do record.
     */
    public function show($what_we_do_id)
    {
        try {
            // Check if table exists
            if (!Schema::hasTable('tbl_what_we_do')) {
                \Log::error('Table tbl_what_we_do does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $record = WhatWeDo::select('what_we_do_id', 'category', 'description', 'img_file', 'created_at', 'updated_at')
                ->find($what_we_do_id);

            if (!$record) {
                \Log::warning('What we do record not found for ID: ' . $what_we_do_id);
                return response()->json(['message' => 'What we do record not found'], 404);
            }

            \Log::info('Successfully fetched what we do record.', ['what_we_do_id' => $what_we_do_id]);

            return response()->json(['record' => $record], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching what we do record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch what we do record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified what we do record using POST.
     */
    public function update(Request $request, $what_we_do_id)
    {
        \Log::info('What we do update request data for ID ' . $what_we_do_id . ': ', $request->all());

        try {
            $record = WhatWeDo::find($what_we_do_id);
            if (!$record) {
                \Log::warning('What we do record not found for ID: ' . $what_we_do_id);
                return response()->json(['message' => 'What we do record not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:255',
                'description' => 'nullable|string',
                'img_file' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation failed for what we do update ID ' . $what_we_do_id . ': ', $validator->errors()->toArray());
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            // Handle img_file upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                // Delete old image if it exists
                if ($record->img_file && File::exists(public_path($record->img_file))) {
                    File::delete(public_path($record->img_file));
                    \Log::info('Deleted old image: ' . $record->img_file);
                }

                $uploadPath = public_path('uploads/whatwedo');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                    \Log::info('Created uploads/whatwedo directory at: ' . $uploadPath);
                }

                $file = $request->file('img_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['img_file'] = 'uploads/whatwedo/' . $fileName;
                \Log::info('New image uploaded: ' . $data['img_file']);
            } else {
                $data['img_file'] = $record->img_file;
                \Log::info('No new image uploaded, preserving existing: ' . ($record->img_file ?: 'none'));
            }

            $record->fill($data)->save();
            \Log::info('What we do record updated successfully for ID: ' . $what_we_do_id);

            return response()->json([
                'message' => 'What we do record updated successfully.',
                'record' => $record->fresh()
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error updating what we do record for ID ' . $what_we_do_id . ': ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to update what we do record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified what we do record.
     */
    public function destroy($what_we_do_id)
    {
        try {
            $record = WhatWeDo::find($what_we_do_id);
            if (!$record) {
                \Log::warning('What we do record not found for ID: ' . $what_we_do_id);
                return response()->json(['message' => 'What we do record not found'], 404);
            }

            // Delete img_file if it exists
            if ($record->img_file && File::exists(public_path($record->img_file))) {
                File::delete(public_path($record->img_file));
                \Log::info('Deleted image: ' . $record->img_file);
            }

            $record->delete();
            \Log::info('What we do record deleted successfully for ID: ' . $what_we_do_id);

            return response()->json(['message' => 'What we do record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting what we do record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to delete what we do record.', 'details' => $e->getMessage()], 500);
        }
    }
}