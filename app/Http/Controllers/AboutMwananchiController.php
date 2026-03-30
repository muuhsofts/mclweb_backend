<?php

namespace App\Http\Controllers;

use App\Models\AboutMwananchi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AboutMwananchiController extends Controller
{
    /**
     * The path relative to the public directory where PDF files are stored.
     * It is assumed this directory already exists on the server.
     */
    private const PDF_UPLOAD_PATH = 'uploads';

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestRecord', 'allRecords']);
    }

    /**
     * Get all records, sorted newest first.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $records = AboutMwananchi::orderBy('id', 'desc')->get();
            return response()->json(['records' => $records], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error fetching AboutMwananchi records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch records.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all records, sorted oldest first.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function allRecords()
    {
        try {
            $records = AboutMwananchi::orderBy('id', 'asc')->get();
            return response()->json(['records' => $records], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error fetching all AboutMwananchi records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch records.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the total count of records.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countRecords()
    {
        try {
            $count = AboutMwananchi::count();
            return response()->json(['count_records' => $count], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error counting AboutMwananchi records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count records.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the single latest record based on creation date.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function latestRecord()
    {
        try {
            $latestRecord = AboutMwananchi::latest()->first();

            if (!$latestRecord) {
                return response()->json(['message' => 'No record found'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['record' => $latestRecord], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Error fetching latest AboutMwananchi record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest record.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new record. This assumes the upload directory exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_link' => 'nullable|url|regex:/^https:\/\/www\.youtube\.com\/embed\/[a-zA-Z0-9_-]+(\?si=[a-zA-Z0-9_-]+)?$/',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240', // 10 MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('pdf_file')) {
                $file = $request->file('pdf_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                // Move the file to the destination. No directory creation logic.
                $file->move(public_path(self::PDF_UPLOAD_PATH), $filename);
                $data['pdf_file'] = self::PDF_UPLOAD_PATH . '/' . $filename;
            }

            $record = AboutMwananchi::create($data);
            return response()->json(['message' => 'Record created successfully', 'record' => $record], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Error creating AboutMwananchi record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create record.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a single record by its ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $record = AboutMwananchi::findOrFail($id);
            return response()->json(['record' => $record], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Record not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error fetching AboutMwananchi record ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching the record.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing record. This assumes the upload directory exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'video_link' => 'nullable|url|regex:/^https:\/\/www\.youtube\.com\/embed\/[a-zA-Z0-9_-]+(\?si=[a-zA-Z0-9_-]+)?$/',
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $record = AboutMwananchi::findOrFail($id);
            $data = $validator->validated();

            if ($request->hasFile('pdf_file')) {
                // Delete old file if it exists
                if ($record->pdf_file && File::exists(public_path($record->pdf_file))) {
                    File::delete(public_path($record->pdf_file));
                }
                
                $file = $request->file('pdf_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                // Move the new file. No directory creation logic.
                $file->move(public_path(self::PDF_UPLOAD_PATH), $filename);
                $data['pdf_file'] = self::PDF_UPLOAD_PATH . '/' . $filename;
            
            } elseif ($request->has('pdf_file') && $request->input('pdf_file') === null) {
                if ($record->pdf_file && File::exists(public_path($record->pdf_file))) {
                    File::delete(public_path($record->pdf_file));
                }
                $data['pdf_file'] = null;
            }

            $record->update($data);
            return response()->json(['message' => 'Record updated successfully', 'record' => $record->fresh()], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Record not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error updating AboutMwananchi record ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update record.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $record = AboutMwananchi::findOrFail($id);

            if ($record->pdf_file && File::exists(public_path($record->pdf_file))) {
                File::delete(public_path($record->pdf_file));
            }

            $record->delete();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Record not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error deleting AboutMwananchi record ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete record.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}