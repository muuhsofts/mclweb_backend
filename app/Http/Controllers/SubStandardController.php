<?php

namespace App\Http\Controllers;

use App\Models\SubStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SubStandardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * Display a listing of sub-standard records.
     */
    public function index()
    {
        try {
            $subStandardRecords = SubStandard::with('ourStandard')->orderBy('subStandard_id', 'desc')->get();
            return response()->json(['sub_standard' => $subStandardRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching sub-standard records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sub-standard records.'], 500);
        }
    }

    /**
     * Display the latest sub-standard record based on created_at.
     */
    public function latest()
    {
        try {
            $latestSubStandard = SubStandard::with('ourStandard')->orderBy('created_at', 'desc')->first();
            if (!$latestSubStandard) {
                return response()->json(['message' => 'No Sub-Standard record found'], 404);
            }
            return response()->json(['sub_standard' => $latestSubStandard], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest sub-standard record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest sub-standard record.'], 500);
        }
    }

    /**
     * Store a newly created sub-standard record.
     */
    public function store(Request $request)
    {
        \Log::info('Sub-Standard store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'subStandard_category' => 'required|string|max:255',
            'our_id' => 'required|exists:our_standard,our_id',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Sub-Standard store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            $subStandard = SubStandard::create($data);
            return response()->json(['message' => 'Sub-Standard record created successfully', 'sub_standard' => $subStandard->load('ourStandard')], 201);
        } catch (Exception $e) {
            \Log::error('Error creating sub-standard record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create sub-standard record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sub-standard record.
     */
    public function show($subStandard_id)
    {
        $subStandard = SubStandard::with('ourStandard')->find($subStandard_id);
        if (!$subStandard) {
            return response()->json(['message' => 'Sub-Standard record not found'], 404);
        }
        return response()->json(['sub_standard' => $subStandard], 200);
    }

    /**
     * Update the specified sub-standard record using POST.
     */
    public function update(Request $request, $subStandard_id)
    {
        \Log::info('Sub-Standard update request data for ID ' . $subStandard_id . ': ', $request->all());

        $subStandard = SubStandard::find($subStandard_id);
        if (!$subStandard) {
            \Log::warning('Sub-Standard record not found for ID: ' . $subStandard_id);
            return response()->json(['message' => 'Sub-Standard record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subStandard_category' => 'required|string|max:255',
            'our_id' => 'required|exists:our_standard,our_id',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Sub-Standard update ID ' . $subStandard_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();
            \Log::info('Validated data for update ID ' . $subStandard_id . ': ', $data);

            $subStandard->fill($data)->save();
            \Log::info('Sub-Standard record updated successfully for ID: ' . $subStandard_id);
            return response()->json([
                'message' => 'Sub-Standard record updated successfully.',
                'sub_standard' => $subStandard->fresh()->load('ourStandard')
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error updating sub-standard record for ID ' . $subStandard_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update sub-standard record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified sub-standard record.
     */
    public function destroy($subStandard_id)
    {
        $subStandard = SubStandard::find($subStandard_id);
        if (!$subStandard) {
            \Log::warning('Sub-Standard record not found for ID: ' . $subStandard_id);
            return response()->json(['message' => 'Sub-Standard record not found'], 404);
        }

        try {
            $subStandard->delete();
            \Log::info('Sub-Standard record deleted successfully for ID: ' . $subStandard_id);
            return response()->json(['message' => 'Sub-Standard record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting sub-standard record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete sub-standard record.', 'details' => $e->getMessage()], 500);
        }
    }
}
