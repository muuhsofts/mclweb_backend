<?php

namespace App\Http\Controllers;

use App\Models\FtPink130Home;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class Pink130HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show','pink130Sliders']);
    }

    /**
     * Display a listing of ft pink 130 home records.
     */
    public function index()
    {
        try {
            $ftPink130Homes = FtPink130Home::orderBy('ft_pink_id', 'desc')->get();
            return response()->json(['ft_pink_130_homes' => $ftPink130Homes], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching ft pink 130 home records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch ft pink 130 home records.'], 500);
        }
    }

    public function pink130Sliders()
    {
        try {
            $ftPink130Homes = FtPink130Home::orderBy('ft_pink_id', 'desc')->get();
            return response()->json(['ft_pink_130_homes' => $ftPink130Homes], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching ft pink 130 home records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch ft pink 130 home records.'], 500);
        }
    }



    /**
     * Display the latest ft pink 130 home record based on created_at.
     */
    public function latest()
    {
        try {
            $latestFtPink130Home = FtPink130Home::orderBy('created_at', 'desc')->first();
            
            if (!$latestFtPink130Home) {
                return response()->json(['message' => 'No Ft Pink 130 Home record found'], 404);
            }

            return response()->json(['ft_pink_130_home' => $latestFtPink130Home], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest ft pink 130 home record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest ft pink 130 home record.'], 500);
        }
    }

    /**
     * Store a newly created ft pink 130 home record.
     */
    public function store(Request $request)
    {
        \Log::info('Ft Pink 130 Home store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Ft Pink 130 Home store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle home_img upload
            if ($request->hasFile('home_img') && $request->file('home_img')->isValid()) {
                $image = $request->file('home_img');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('Uploads/ft_pink_130_home_images');
                
                // Ensure the directory exists
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['home_img'] = 'Uploads/ft_pink_130_home_images/' . $imageName;
                \Log::info('Home image uploaded: ' . $data['home_img']);
            }

            $ftPink130Home = FtPink130Home::create($data);
            return response()->json(['message' => 'Ft Pink 130 Home record created successfully', 'ft_pink_130_home' => $ftPink130Home], 201);
        } catch (Exception $e) {
            \Log::error('Error creating ft pink 130 home record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create ft pink 130 home record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified ft pink 130 home record.
     */
    public function show($ft_pink_id)
    {
        $ftPink130Home = FtPink130Home::find($ft_pink_id);

        if (!$ftPink130Home) {
            return response()->json(['message' => 'Ft Pink 130 Home record not found'], 404);
        }

        return response()->json(['ft_pink_130_home' => $ftPink130Home], 200);
    }

    /**
     * Update the specified ft pink 130 home record using POST.
     */
    public function update(Request $request, $ft_pink_id)
    {
        \Log::info('Ft Pink 130 Home update request data: ', $request->all());

        $ftPink130Home = FtPink130Home::find($ft_pink_id);
        if (!$ftPink130Home) {
            return response()->json(['message' => 'Ft Pink 130 Home record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for Ft Pink 130 Home update: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle home_img upload
            if ($request->hasFile('home_img') && $request->file('home_img')->isValid()) {
                // Delete old home_img if it exists
                if ($ftPink130Home->home_img && file_exists(public_path($ftPink130Home->home_img))) {
                    unlink(public_path($ftPink130Home->home_img));
                    \Log::info('Deleted old home image: ' . $ftPink130Home->home_img);
                }

                $image = $request->file('home_img');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('Uploads/ft_pink_130_home_images');
                
                // Ensure the directory exists
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['home_img'] = 'Uploads/ft_pink_130_home_images/' . $imageName;
                \Log::info('New home image uploaded: ' . $data['home_img']);
            } else {
                $data['home_img'] = $ftPink130Home->home_img;
                \Log::info('No new home image uploaded, preserving existing: ' . ($ftPink130Home->home_img ?: 'none'));
            }

            $ftPink130Home->fill($data)->save();
            return response()->json(['message' => 'Ft Pink 130 Home record updated successfully.', 'ft_pink_130_home' => $ftPink130Home->fresh()], 200);
        } catch (Exception $e) {
            \Log::error('Error updating ft pink 130 home record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update ft pink 130 home record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified ft pink 130 home record.
     */
    public function destroy($ft_pink_id)
    {
        $ftPink130Home = FtPink130Home::find($ft_pink_id);
        if (!$ftPink130Home) {
            return response()->json(['message' => 'Ft Pink 130 Home record not found'], 404);
        }

        try {
            // Delete home_img if it exists
            if ($ftPink130Home->home_img && file_exists(public_path($ftPink130Home->home_img))) {
                unlink(public_path($ftPink130Home->home_img));
                \Log::info('Deleted home image: ' . $ftPink130Home->home_img);
            }

            $ftPink130Home->delete();
            return response()->json(['message' => 'Ft Pink 130 Home record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting ft pink 130 home record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete ft pink 130 home record.', 'details' => $e->getMessage()], 500);
        }
    }
}
