<?php

namespace App\Http\Controllers;

use App\Models\Leadership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // Import Rule
use Exception;

class LeadershipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show','latestleadership','allLeadership']);
    }

    /**
     * Display a listing of leadership records.
     */
    public function index()
    {
        try {
            $leadership = Leadership::orderBy('leadership_id', 'desc')->get();
            return response()->json(['leadership' => $leadership], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching leadership records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch leadership records.'], 500);
        }
    }
    public function allLeadership()
    {
        try {
            $leadership = Leadership::orderBy('leadership_id', 'asc')->get();
            return response()->json(['leadership' => $leadership], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching leadership records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch leadership records.'], 500);
        }
    }
   
    /**
     * Display the latest leadership record based on created_at.
     */
    public function latestleadership()
    {
        try {
            $latestLeadership = Leadership::orderBy('created_at', 'desc')->first();
            
            if (!$latestLeadership) {
                return response()->json(['message' => 'No Leadership record found'], 404);
            }

            return response()->json(['leadership' => $latestLeadership], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest leadership record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest leadership record.'], 500);
        }
    }

    /**
     * Count the total number of leadership records.
     */
    public function countLeadership()
    {
        try {
            $count = Leadership::count();
            return response()->json(['count_leaders' => $count], 200);
        } catch (Exception $e) {
            \Log::error('Error counting leadership records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count leadership records.'], 500);
        }
    }

    /**
     * Store a newly created leadership record.
     */
    public function store(Request $request)
    {
        \Log::info('Leadership store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'leader_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'level' => ['required', 'string', Rule::in(['Board of Directors', 'Management'])], // Add validation for level
            'leader_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Leadership store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('leader_image') && $request->file('leader_image')->isValid()) {
                $image = $request->file('leader_image');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/leadership_images');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['leader_image'] = 'uploads/leadership_images/' . $imageName;
                \Log::info('Leader image uploaded: ' . $data['leader_image']);
            }

            $leadership = Leadership::create($data);
            return response()->json(['message' => 'Leadership record created successfully', 'leadership' => $leadership], 201);
        } catch (Exception $e) {
            \Log::error('Error creating leadership record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create leadership record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified leadership record.
     */
    public function show($leadership_id)
    {
        $leadership = Leadership::find($leadership_id);

        if (!$leadership) {
            return response()->json(['message' => 'Leadership record not found'], 404);
        }

        return response()->json(['leadership' => $leadership], 200);
    }

    /**
     * Update the specified leadership record using POST.
     */
    public function update(Request $request, $leadership_id)
    {
        \Log::info('Leadership update request data: ', $request->all());

        $leadership = Leadership::find($leadership_id);
        if (!$leadership) {
            return response()->json(['message' => 'Leadership record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'leader_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'level' => ['required', 'string', Rule::in(['Board of Directors', 'Management'])], // Add validation for level
            'leader_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Leadership update: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('leader_image') && $request->file('leader_image')->isValid()) {
                if ($leadership->leader_image && file_exists(public_path($leadership->leader_image))) {
                    unlink(public_path($leadership->leader_image));
                    \Log::info('Deleted old leader image: ' . $leadership->leader_image);
                }

                $image = $request->file('leader_image');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/leadership_images');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['leader_image'] = 'uploads/leadership_images/' . $imageName;
                \Log::info('New leader image uploaded: ' . $data['leader_image']);
            } else {
                $data['leader_image'] = $leadership->leader_image;
                \Log::info('No new leader image uploaded, preserving existing: ' . ($leadership->leader_image ?: 'none'));
            }

            $leadership->fill($data)->save();
            return response()->json(['message' => 'Leadership record updated successfully.', 'leadership' => $leadership->fresh()], 200);
        } catch (Exception $e) {
            \Log::error('Error updating leadership record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update leadership record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified leadership record.
     */
    public function destroy($leadership_id)
    {
        $leadership = Leadership::find($leadership_id);
        if (!$leadership) {
            return response()->json(['message' => 'Leadership record not found'], 404);
        }

        try {
            if ($leadership->leader_image && file_exists(public_path($leadership->leader_image))) {
                unlink(public_path($leadership->leader_image));
                \Log::info('Deleted leader image: ' . $leadership->leader_image);
            }

            $leadership->delete();
            return response()->json(['message' => 'Leadership record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting leadership record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete leadership record.', 'details' => $e->getMessage()], 500);
        }
    }
}