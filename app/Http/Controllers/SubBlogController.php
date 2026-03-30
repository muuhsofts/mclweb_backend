<?php

namespace App\Http\Controllers;

use App\Models\SubBlog;
use App\Models\Blog; // This was already included, which is great.
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SubBlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestSubBlog', 'allSubBlogs', 'blogsDropDown']);
    }

    /**
     * Display a listing of sub-blog records with their associated blog.
     */
    public function index()
    {
        try {
            // MODIFIED: Eager load the 'blog' relationship to include parent blog data.
            $subBlogs = SubBlog::with('blog')->orderBy('sublog_id', 'desc')->get();
            return response()->json(['sub_blogs' => $subBlogs], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching sub-blog records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sub-blog records.'], 500);
        }
    }

    /**
     * Display all sub-blog records with their associated blog.
     */
    public function allSubBlogs()
    {
        try {
            // MODIFIED: Eager load the 'blog' relationship.
            $subBlogs = SubBlog::with('blog')->orderBy('sublog_id', 'desc')->get();
            return response()->json(['sub_blogs' => $subBlogs], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching sub-blog records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sub-blog records.'], 500);
        }
    }

    /**
     * Display the latest sub-blog record with its associated blog.
     */
    public function latestSubBlog()
    {
        try {
            // MODIFIED: Eager load the 'blog' relationship.
            $latestSubBlog = SubBlog::with('blog')->orderBy('created_at', 'desc')->first();
            
            if (!$latestSubBlog) {
                return response()->json(['message' => 'No sub-blog record found'], 404);
            }

            return response()->json(['sub_blog' => $latestSubBlog], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest sub-blog record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest sub-blog record.'], 500);
        }
    }

    /**
     * Store a newly created sub-blog record.
     */
    public function store(Request $request)
    {
        \Log::info('Sub-blog store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            // MODIFIED: Added blog_id to validation. It's required and must exist in the blogs table.
            'blog_id' => 'required|integer|exists:blogs,blog_id',
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_file' => 'nullable|file|mimetypes:video/*|max:40240',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'url_link' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for sub-blog store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle video_file upload
            if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
                $video = $request->file('video_file');
                $videoName = time() . '_' . preg_replace('/\s+/', '_', $video->getClientOriginalName());
                $uploadPath = public_path('uploads/sub_blog_videos');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $video->move($uploadPath, $videoName);
                $data['video_file'] = 'uploads/sub_blog_videos/' . $videoName;
                \Log::info('Video file uploaded: ' . $data['video_file']);
            }

            // Handle image_file upload
            if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
                $image = $request->file('image_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/sub_blog_images');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['image_file'] = 'uploads/sub_blog_images/' . $imageName;
                \Log::info('Image file uploaded: ' . $data['image_file']);
            }
            
            // The validated data now includes blog_id, so it will be saved automatically.
            $subBlog = SubBlog::create($data);
            
            // MODIFIED: Load the blog relationship on the newly created record before returning.
            $subBlog->load('blog');
            
            return response()->json(['message' => 'Sub-blog record created successfully', 'sub_blog' => $subBlog], 201);
        } catch (Exception $e) {
            \Log::error('Error creating sub-blog record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create sub-blog record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sub-blog record with its associated blog.
     */
    public function show($sublog_id)
    {
        // MODIFIED: Eager load the 'blog' relationship.
        $subBlog = SubBlog::with('blog')->find($sublog_id);

        if (!$subBlog) {
            return response()->json(['message' => 'Sub-blog record not found'], 404);
        }

        return response()->json(['sub_blog' => $subBlog], 200);
    }

    /**
     * Update the specified sub-blog record.
     */
    public function update(Request $request, $sublog_id)
    {
        \Log::info('Sub-blog update request data: ', $request->all());

        $subBlog = SubBlog::find($sublog_id);
        if (!$subBlog) {
            return response()->json(['message' => 'Sub-blog record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            // MODIFIED: Added blog_id. 'sometimes' means it's only validated if present.
            'blog_id' => 'sometimes|integer|exists:blogs,blog_id',
            'heading' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'video_file' => 'nullable|file|mimetypes:video/*|max:10240',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'url_link' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for sub-blog update: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle video_file upload
            if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
                if ($subBlog->video_file && file_exists(public_path($subBlog->video_file))) {
                    unlink(public_path($subBlog->video_file));
                    \Log::info('Deleted old video file: ' . $subBlog->video_file);
                }

                $video = $request->file('video_file');
                $videoName = time() . '_' . preg_replace('/\s+/', '_', $video->getClientOriginalName());
                $uploadPath = public_path('uploads/sub_blog_videos');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $video->move($uploadPath, $videoName);
                $data['video_file'] = 'uploads/sub_blog_videos/' . $videoName;
                \Log::info('New video file uploaded: ' . $data['video_file']);
            }

            // Handle image_file upload
            if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
                if ($subBlog->image_file && file_exists(public_path($subBlog->image_file))) {
                    unlink(public_path($subBlog->image_file));
                    \Log::info('Deleted old image file: ' . $subBlog->image_file);
                }

                $image = $request->file('image_file');
                $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
                $uploadPath = public_path('uploads/sub_blog_images');
                
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $data['image_file'] = 'uploads/sub_blog_images/' . $imageName;
                \Log::info('New image file uploaded: ' . $data['image_file']);
            }

            $subBlog->fill($data)->save();
            
            // MODIFIED: Use fresh() with the relationship to get the updated model with its blog.
            return response()->json(['message' => 'Sub-blog record updated successfully.', 'sub_blog' => $subBlog->fresh('blog')], 200);
        } catch (Exception $e) {
            \Log::error('Error updating sub-blog record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update sub-blog record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified sub-blog record.
     */
    public function destroy($sublog_id)
    {
        $subBlog = SubBlog::find($sublog_id);
        if (!$subBlog) {
            return response()->json(['message' => 'Sub-blog record not found'], 404);
        }

        try {
            // Delete video_file if it exists
            if ($subBlog->video_file && file_exists(public_path($subBlog->video_file))) {
                unlink(public_path($subBlog->video_file));
                \Log::info('Deleted video file: ' . $subBlog->video_file);
            }

            // Delete image_file if it exists
            if ($subBlog->image_file && file_exists(public_path($subBlog->image_file))) {
                unlink(public_path($subBlog->image_file));
                \Log::info('Deleted image file: ' . $subBlog->image_file);
            }

            $subBlog->delete();
            return response()->json(['message' => 'Sub-blog record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Error deleting sub-blog record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete sub-blog record.', 'details' => $e->getMessage()], 500);
        }
    }

    
}