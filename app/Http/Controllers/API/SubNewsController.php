<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File; // Use the File facade

class SubNewsController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'subNews']);
    }
   
    public function index()
    {
        // Eager load the 'news' relationship to prevent N+1 query problems.
        $subNews = SubNews::with('news')->latest()->get();
        return response()->json(['sub_news' => $subNews], 200);
    }


       public function subNews()
    {
        // Eager load the 'news' relationship to prevent N+1 query problems.
        $subNews = SubNews::with('news')->latest()->get();
        return response()->json(['sub_news' => $subNews], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'news_id' => 'required|exists:news,news_id',
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'img_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'twitter_link' => 'nullable|url|max:255',
            'facebook_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'email_url' => 'nullable|string|max:255', // Changed to string to allow mailto: links, 'url' is too strict
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except('img_url'); // Get all data except the file for now

        if ($request->hasFile('img_url')) {
            $file = $request->file('img_url');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/subnews'), $filename);
            $data['img_url'] = 'uploads/subnews/' . $filename; // Store relative path without leading slash
        }

        $subNews = SubNews::create($data);
        return response()->json(['message' => 'Sub-news created successfully', 'sub_news' => $subNews->load('news')], 201);
    }
    
    // REFINEMENT: Use Route Model Binding (SubNews $subNews)
    // Laravel will automatically find the SubNews by its primary key or return a 404 error.
    public function show(SubNews $subNews)
    {
        return response()->json(['sub_news' => $subNews->load('news')], 200);
    }

    // REFINEMENT: Use Route Model Binding (SubNews $subNews)
    public function update(Request $request, SubNews $subNews)
    {
        $validator = Validator::make($request->all(), [
            'news_id' => 'required|exists:news,news_id',
            'heading' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'img_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // This validation is for new uploads
            'twitter_link' => 'nullable|url|max:255',
            'facebook_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'email_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except('img_url');

        if ($request->hasFile('img_url')) {
            // Delete old image if it exists
            if ($subNews->img_url && File::exists(public_path($subNews->img_url))) {
                File::delete(public_path($subNews->img_url));
            }
            
            $file = $request->file('img_url');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/subnews'), $filename);
            $data['img_url'] = 'uploads/subnews/' . $filename;
        }

        $subNews->update($data);
        
        // Return the updated model with the relationship loaded
        return response()->json(['message' => 'Sub-news updated successfully', 'sub_news' => $subNews->load('news')], 200);
    }

    // REFINEMENT: Use Route Model Binding (SubNews $subNews)
    public function destroy(SubNews $subNews)
    {
        if ($subNews->img_url && File::exists(public_path($subNews->img_url))) {
            File::delete(public_path($subNews->img_url));
        }
        $subNews->delete();
        return response()->json(['message' => 'Sub-news deleted successfully'], 200);
    }
}