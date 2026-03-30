<?php

namespace App\Http\Controllers;

use App\Models\Value;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ValuesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'allValues']);
    }

    /**
     * Display a listing of value records.
     */
    public function index()
    {
        try {
            $values = Value::orderBy('value_id', 'desc')->get();
            return response()->json(['values' => $values], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching value records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch value records.'], 500);
        }
    }

    /**
     * Display all value records (alternative endpoint).
     */
    public function allValues()
    {
        try {
            $values = Value::orderBy('value_id', 'desc')->get();
            return response()->json(['values' => $values], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching all value records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch value records.'], 500);
        }
    }

    /**
     * Store a newly created value record.
     */
    public function store(Request $request)
    {
        \Log::info('Value store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Value store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                $image = $request->file('img_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/values');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['img_file'] = 'uploads/values/' . $imageName;
                \Log::info('Value image uploaded: ' . $data['img_file']);
            }

            $value = Value::create($data);
            return response()->json(['message' => 'Value record created successfully', 'value' => $value], 201);
        } catch (Exception $e) {
            \Log::error('Error creating value record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create value record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified value record.
     */
    public function show($value_id)
    {
        $value = Value::find($value_id);

        if (!$value) {
            return response()->json(['message' => 'Value record not found'], 404);
        }

        return response()->json(['value' => $value], 200);
    }

    /**
     * Update the specified value record using POST.
     */
    public function update(Request $request, $value_id)
    {
        \Log::info('Value update request data: ', $request->all());

        $value = Value::find($value_id);
        if (!$value) {
            return response()->json(['message' => 'Value record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Value update: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                if ($value->img_file && file_exists(public_path($value->img_file))) {
                    unlink(public_path($value->img_file));
                    \Log::info('Deleted old value image: ' . $value->img_file);
                }

                $image = $request->file('img_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/values');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['img_file'] = 'uploads/values/' . $imageName;
                \Log::info('New value image uploaded: ' . $data['img_file']);
            } else {
                $data['img_file'] = $value->img_file;
                \Log::info('No new value image uploaded, preserving existing: ' . ($value->img_file ?: 'none'));
            }

            $value->fill($data)->save();
            return response()->json(['message' => 'Value record updated successfully.', 'value' => $value->fresh()], 200);
        } catch (Exception $e) {
            \Log::error('Error updating value record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update value record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified value record.
     */
    public function destroy($value_id)
    {
        $value = Value::find($value_id);
        if (!$value) {
            return response()->json(['message' => 'Value record not found'], 404);
        }

        try {
            if ($value->img_file && file_exists(public_path($value->img_file))) {
                unlink(public_path($value->img_file));
                \Log::info('Deleted value image: ' . $value->img_file);
            }

            $value->delete();
            return response()->json(['message' => 'Value record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting value record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete value record.', 'details' => $e->getMessage()], 500);
        }
    }
}
