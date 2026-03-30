<?php

namespace App\Http\Controllers;

use App\Models\EarlyCareer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class EarlyCareersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'allEarlyCareers','latestEarlyCareer']);
    }

    /**
     * Display a listing of early careers records.
     */
    public function index()
    {
        try {
            $earlyCareers = EarlyCareer::orderBy('early_career_id', 'desc')->get();
            return response()->json(['early_careers' => $earlyCareers], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching early careers records: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Failed to fetch early careers records.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
 * Display the latest early career record based on created_at.
 */
public function latestEarlyCareer()
{
    try {
        $latestEarlyCareer = EarlyCareer::orderBy('created_at', 'desc')->first();
        
        if (!$latestEarlyCareer) {
            return response()->json(['message' => 'No early career record found'], 404);
        }

        return response()->json(['early_career' => $latestEarlyCareer], 200);
    } catch (Exception $e) {
        \Log::error('Error fetching latest early career record: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'error' => 'Failed to fetch latest early career record.',
            'details' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    /**
     * Display all early careers records (alternative endpoint).
     */
    public function allEarlyCareers()
    {
        try {
            $earlyCareers = EarlyCareer::orderBy('early_career_id', 'desc')->get();
            return response()->json(['early_careers' => $earlyCareers], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching all early careers records: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Failed to fetch early careers records.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created early career record.
     */
    public function store(Request $request)
    {
        \Log::info('EarlyCareer store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'video_file' => 'nullable|file|mimes:mp4,avi,mov,wmv|max:10240', // 10MB max
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for EarlyCareer store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                $image = $request->file('img_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/early_careers/images');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['img_file'] = 'uploads/early_careers/images/' . $imageName;
                \Log::info('EarlyCareer image uploaded: ' . $data['img_file']);
            }

            // Handle video upload
            if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
                $video = $request->file('video_file');
                $videoName = time() . '_' . preg_replace('/\s+/', '_', $video->getClientOriginalName());
                $uploadPath = public_path('uploads/early_careers/videos');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $video->move($uploadPath, $videoName);
                $data['video_file'] = 'uploads/early_careers/videos/' . $videoName;
                \Log::info('EarlyCareer video uploaded: ' . $data['video_file']);
            }

            $earlyCareer = EarlyCareer::create($data);
            return response()->json(['message' => 'Early career record created successfully', 'early_career' => $earlyCareer], 201);
        } catch (Exception $e) {
            \Log::error('Error creating early career record: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Failed to create early career record.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified early career record.
     */
    public function show($early_career_id)
    {
        $earlyCareer = EarlyCareer::find($early_career_id);

        if (!$earlyCareer) {
            return response()->json(['message' => 'Early career record not found'], 404);
        }

        return response()->json(['early_career' => $earlyCareer], 200);
    }

    /**
     * Update the specified early career record using POST.
     */
    public function update(Request $request, $early_career_id)
    {
        \Log::info('EarlyCareer update request data: ', $request->all());

        $earlyCareer = EarlyCareer::find($early_career_id);
        if (!$earlyCareer) {
            return response()->json(['message' => 'Early career record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'video_file' => 'nullable|file|mimes:mp4,avi,mov,wmv|max:10240',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for EarlyCareer update: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                if ($earlyCareer->img_file && file_exists(public_path($earlyCareer->img_file))) {
                    unlink(public_path($earlyCareer->img_file));
                    \Log::info('Deleted old early career image: ' . $earlyCareer->img_file);
                }

                $image = $request->file('img_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/early_careers/images');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['img_file'] = 'uploads/early_careers/images/' . $imageName;
                \Log::info('New early career image uploaded: ' . $data['img_file']);
            } else {
                $data['img_file'] = $earlyCareer->img_file;
                \Log::info('No new early career image uploaded, preserving existing: ' . ($earlyCareer->img_file ?: 'none'));
            }

            // Handle video upload
            if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
                if ($earlyCareer->video_file && file_exists(public_path($earlyCareer->video_file))) {
                    unlink(public_path($earlyCareer->video_file));
                    \Log::info('Deleted old early career video: ' . $earlyCareer->video_file);
                }

                $video = $request->file('video_file');
                $videoName = time() . '_' . preg_replace('/\s+/', '_', $video->getClientOriginalName());
                $uploadPath = public_path('uploads/early_careers/videos');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $video->move($uploadPath, $videoName);
                $data['video_file'] = 'uploads/early_careers/videos/' . $videoName;
                \Log::info('New early career video uploaded: ' . $data['video_file']);
            } else {
                $data['video_file'] = $earlyCareer->video_file;
                \Log::info('No new early career video uploaded, preserving existing: ' . ($earlyCareer->video_file ?: 'none'));
            }

            $earlyCareer->fill($data)->save();
            return response()->json(['message' => 'Early career record updated successfully.', 'early_career' => $earlyCareer->fresh()], 200);
        } catch (Exception $e) {
            \Log::error('Error updating early career record: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Failed to update early career record.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified early career record.
     */
    public function destroy($early_career_id)
    {
        $earlyCareer = EarlyCareer::find($early_career_id);
        if (!$earlyCareer) {
            return response()->json(['message' => 'Early career record not found'], 404);
        }

        try {
            if ($earlyCareer->img_file && file_exists(public_path($earlyCareer->img_file))) {
                unlink(public_path($earlyCareer->img_file));
                \Log::info('Deleted early career image: ' . $earlyCareer->img_file);
            }
            if ($earlyCareer->video_file && file_exists(public_path($earlyCareer->video_file))) {
                unlink(public_path($earlyCareer->video_file));
                \Log::info('Deleted early career video: ' . $earlyCareer->video_file);
            }

            $earlyCareer->delete();
            return response()->json(['message' => 'Early career record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting early career record: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Failed to delete early career record.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
