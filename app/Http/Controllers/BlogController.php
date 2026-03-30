<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class BlogController extends Controller
{
    public function __construct()
    {
        // Protect all methods except the public-facing ones.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestBlog', 'allBlogs', 'blogsDropDown']);
    }

    /**
     * Display a listing of blog records for admin view.
     */
    public function index()
    {
        // Return the collection directly. Laravel automatically converts it to JSON.
        return Blog::orderBy('blog_id', 'desc')->get();
    }

    /**
     * Display all blog records for public view.
     */
    public function allBlogs()
    {
        // This endpoint is public via middleware.
        return Blog::orderBy('blog_id', 'desc')->get();
    }

    /**
     * Display the latest blog record.
     */
    public function latestBlog()
    {
        // firstOrFail() gets the first record or throws a ModelNotFoundException
        // which Laravel converts to a 404 response.
        return Blog::orderBy('created_at', 'desc')->firstOrFail();
    }

    /**
     * Provide a list of blogs for a dropdown menu (ID and heading).
     */
    public function blogsDropDown()
    {
        // Return the selected fields directly as a collection.
        return Blog::select('blog_id', 'heading')->orderBy('blog_id', 'desc')->get();
    }

    /**
     * Store a newly created blog record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $blog = Blog::create($validator->validated());

            return response()->json([
                'message' => 'Blog record created successfully.',
                'blog' => $blog
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating blog record: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create blog record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified blog record.
     */
    public function show($blog_id)
    {
        // findOrFail automatically handles the 'not found' case.
        return Blog::findOrFail($blog_id);
    }

    /**
     * Update the specified blog record.
     */
    public function update(Request $request, $blog_id)
    {
        // findOrFail ensures the record exists before proceeding.
        $blog = Blog::findOrFail($blog_id);

        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $blog->update($validator->validated());

            return response()->json([
                'message' => 'Blog record updated successfully.',
                'blog' => $blog->fresh()
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating blog record ID {$blog_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update blog record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified blog record.
     */
    public function destroy($blog_id)
    {
        // findOrFail ensures the record exists before attempting deletion.
        $blog = Blog::findOrFail($blog_id);

        try {
            $blog->delete();

            // A 204 No Content response is the standard for a successful deletion.
            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting blog record ID {$blog_id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete blog record.',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}