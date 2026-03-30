<?php

namespace App\Http\Controllers;

use App\Models\StayConnected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class StayConnectedController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'allStayConnected']);
    }

    /**
     * Display a listing of stay connected records.
     */
    public function index()
    {
        try {
            $stayConnected = StayConnected::orderBy('stay_connected_id', 'desc')->get();
            return response()->json(['stay_connected' => $stayConnected], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching stay connected records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch stay connected records.'], 500);
        }
    }

    /**
     * Display all stay connected records (alternative endpoint).
     */
    public function allStayConnected()
    {
        try {
            $stayConnected = StayConnected::orderBy('stay_connected_id', 'desc')->get();
            return response()->json(['stay_connected' => $stayConnected], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching all stay connected records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch stay connected records.'], 500);
        }
    }

    /**
     * Store a newly created stay connected record.
     */
    public function store(Request $request)
    {
        \Log::info('StayConnected store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for StayConnected store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                $image = $request->file('img_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/stay_connected');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['img_file'] = 'uploads/stay_connected/' . $imageName;
                \Log::info('StayConnected image uploaded: ' . $data['img_file']);
            }

            $stayConnected = StayConnected::create($data);
            return response()->json(['message' => 'Stay connected record created successfully', 'stay_connected' => $stayConnected], 201);
        } catch (Exception $e) {
            \Log::error('Error creating stay connected record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create stay connected record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified stay connected record.
     */
    public function show($stay_connected_id)
    {
        $stayConnected = StayConnected::find($stay_connected_id);

        if (!$stayConnected) {
            return response()->json(['message' => 'Stay connected record not found'], 404);
        }

        return response()->json(['stay_connected' => $stayConnected], 200);
    }

    /**
     * Update the specified stay connected record using POST.
     */
    public function update(Request $request, $stay_connected_id)
    {
        \Log::info('StayConnected update request data: ', $request->all());

        $stayConnected = StayConnected::find($stay_connected_id);
        if (!$stayConnected) {
            return response()->json(['message' => 'Stay connected record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for StayConnected update: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                if ($stayConnected->img_file && file_exists(public_path($stayConnected->img_file))) {
                    unlink(public_path($stayConnected->img_file));
                    \Log::info('Deleted old stay connected image: ' . $stayConnected->img_file);
                }

                $image = $request->file('img_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/stay_connected');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['img_file'] = 'uploads/stay_connected/' . $imageName;
                \Log::info('New stay connected image uploaded: ' . $data['img_file']);
            } else {
                $data['img_file'] = $stayConnected->img_file;
                \Log::info('No new stay connected image uploaded, preserving existing: ' . ($stayConnected->img_file ?: 'none'));
            }

            $stayConnected->fill($data)->save();
            return response()->json(['message' => 'Stay connected record updated successfully.', 'stay_connected' => $stayConnected->fresh()], 200);
        } catch (Exception $e) {
            \Log::error('Error updating stay connected record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update stay connected record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified stay connected record.
     */
    public function destroy($stay_connected_id)
    {
        $stayConnected = StayConnected::find($stay_connected_id);
        if (!$stayConnected) {
            return response()->json(['message' => 'Stay connected record not found'], 404);
        }

        try {
            if ($stayConnected->img_file && file_exists(public_path($stayConnected->img_file))) {
                unlink(public_path($stayConnected->img_file));
                \Log::info('Deleted stay connected image: ' . $stayConnected->img_file);
            }

            $stayConnected->delete();
            return response()->json(['message' => 'Stay connected record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting stay connected record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete stay connected record.', 'details' => $e->getMessage()], 500);
        }
    }
}
