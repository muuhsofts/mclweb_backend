<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class NewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestnew', 'allNews', 'newsByid']);
    }

    /**
     * Helper: Upload a file and return the relative path.
     */
    private function uploadFile($file, $directory = 'uploads/news')
    {
        $uploadPath = public_path($directory);
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move($uploadPath, $fileName);
        return $directory . '/' . $fileName;
    }

    /**
     * Get all news records in ascending order (by news_id).
     */
    public function allNews()
    {
        try {
            $newsRecords = News::select(
                'news_id', 'category', 'description', 'news_img', 'pdf_file',
                'read_more_url_lnk', 'images', 'read_more_links', 'created_at', 'updated_at'
            )
                ->orderBy('news_id', 'asc')
                ->get();

            return response()->json(['news' => $newsRecords], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news records.'], 500);
        }
    }

    /**
     * Get the total count of news records.
     */
    public function countNews()
    {
        try {
            $count = News::count();
            return response()->json(['count_news' => $count], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to count news.'], 500);
        }
    }

    /**
     * Get news records in descending order (latest first).
     */
    public function index()
    {
        try {
            $newsRecords = News::select(
                'news_id', 'category', 'description', 'news_img', 'pdf_file',
                'read_more_url_lnk', 'images', 'read_more_links', 'created_at', 'updated_at'
            )
                ->orderBy('news_id', 'desc')
                ->get();

            return response()->json(['news' => $newsRecords], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch news.'], 500);
        }
    }

    /**
     * Get the latest news record (by created_at).
     */
    public function latestnew()
    {
        try {
            $latestNews = News::select(
                'news_id', 'category', 'description', 'news_img', 'pdf_file',
                'read_more_url_lnk', 'images', 'read_more_links', 'created_at', 'updated_at'
            )
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestNews) {
                return response()->json(['message' => 'No news record found'], 404);
            }
            return response()->json(['news' => $latestNews], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch latest news.'], 500);
        }
    }

    /**
     * Alias for show() – get a news record by ID.
     */
    public function newsByid($news_id)
    {
        return $this->show($news_id);
    }

    /**
     * Display a single news record.
     */
    public function show($news_id)
    {
        try {
            $news = News::select(
                'news_id', 'category', 'description', 'news_img', 'pdf_file',
                'read_more_url_lnk', 'images', 'read_more_links', 'created_at', 'updated_at'
            )
                ->find($news_id);

            if (!$news) {
                return response()->json(['message' => 'News record not found'], 404);
            }

            return response()->json(['news' => $news], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch news record.'], 500);
        }
    }

    /**
     * Store a new news record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category'        => 'required|string|max:255',
            'description'     => 'nullable|string',
            'news_img'        => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'pdf_file'        => 'nullable|file|mimes:pdf|max:2048',
            'read_more_url_lnk' => 'nullable|url|max:500',
            'images.*'        => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // multiple
            'read_more_links' => 'nullable|array',
            'read_more_links.*' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Sanitize HTML description
        $data['description'] = clean($data['description'] ?? '');

        // Handle cover image (news_img)
        if ($request->hasFile('news_img') && $request->file('news_img')->isValid()) {
            $data['news_img'] = $this->uploadFile($request->file('news_img'));
        }

        // Handle PDF
        if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
            $data['pdf_file'] = $this->uploadFile($request->file('pdf_file'));
        }

        // Handle multiple gallery images – store paths in 'images' array
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $imagePaths[] = $this->uploadFile($image);
                }
            }
        }
        $data['images'] = $imagePaths;

        // Handle multiple read-more links – ensure it's an array
        $data['read_more_links'] = $data['read_more_links'] ?? [];

        // Create the news record
        $news = News::create($data);

        return response()->json([
            'message' => 'News record created successfully',
            'news'    => $news
        ], 201);
    }

    /**
     * Update an existing news record.
     */
    public function update(Request $request, $news_id)
    {
        try {
            $news = News::find($news_id);
            if (!$news) {
                return response()->json(['message' => 'News record not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'category'        => 'required|string|max:255',
                'description'     => 'nullable|string',
                'news_img'        => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
                'pdf_file'        => 'nullable|file|mimes:pdf|max:2048',
                'read_more_url_lnk' => 'nullable|url|max:500',
                'images.*'        => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
                'read_more_links' => 'nullable|array',
                'read_more_links.*' => 'nullable|url|max:500',
                'remove_image_indices' => 'nullable|array',
                'remove_image_indices.*' => 'integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            // Sanitize description
            $data['description'] = clean($data['description'] ?? '');

            // Handle cover image replacement
            if ($request->hasFile('news_img') && $request->file('news_img')->isValid()) {
                if ($news->news_img && File::exists(public_path($news->news_img))) {
                    File::delete(public_path($news->news_img));
                }
                $data['news_img'] = $this->uploadFile($request->file('news_img'));
            } else {
                $data['news_img'] = $news->news_img;
            }

            // Handle PDF replacement
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                if ($news->pdf_file && File::exists(public_path($news->pdf_file))) {
                    File::delete(public_path($news->pdf_file));
                }
                $data['pdf_file'] = $this->uploadFile($request->file('pdf_file'));
            } else {
                $data['pdf_file'] = $news->pdf_file;
            }

            // Preserve single read_more_url_lnk if not provided
            $data['read_more_url_lnk'] = $request->filled('read_more_url_lnk')
                ? $data['read_more_url_lnk']
                : $news->read_more_url_lnk;

            // ---- Manage gallery images (JSON array) ----
            $existingImages = $news->images ?? [];

            // Remove images by index
            if ($request->has('remove_image_indices')) {
                $indicesToRemove = $request->input('remove_image_indices');
                foreach ($indicesToRemove as $index) {
                    if (isset($existingImages[$index]) && File::exists(public_path($existingImages[$index]))) {
                        File::delete(public_path($existingImages[$index]));
                    }
                    unset($existingImages[$index]);
                }
                // Re-index array
                $existingImages = array_values($existingImages);
            }

            // Add new uploaded images
            $newImagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image->isValid()) {
                        $newImagePaths[] = $this->uploadFile($image);
                    }
                }
            }
            $data['images'] = array_merge($existingImages, $newImagePaths);

            // ---- Manage read_more_links ----
            if ($request->has('read_more_links')) {
                $data['read_more_links'] = $data['read_more_links'] ?? [];
            } else {
                $data['read_more_links'] = $news->read_more_links;
            }

            // Update the news record
            $news->fill($data)->save();

            return response()->json([
                'message' => 'News record updated successfully.',
                'news'    => $news->fresh()
            ], 200);
        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update news record.'], 500);
        }
    }

    /**
     * Delete a news record (and its associated files).
     */
    public function destroy($news_id)
    {
        try {
            $news = News::find($news_id);
            if (!$news) {
                return response()->json(['message' => 'News record not found'], 404);
            }

            // Delete cover image
            if ($news->news_img && File::exists(public_path($news->news_img))) {
                File::delete(public_path($news->news_img));
            }

            // Delete PDF
            if ($news->pdf_file && File::exists(public_path($news->pdf_file))) {
                File::delete(public_path($news->pdf_file));
            }

            // Delete all gallery images (from the 'images' array)
            $galleryImages = $news->images ?? [];
            foreach ($galleryImages as $imagePath) {
                if (File::exists(public_path($imagePath))) {
                    File::delete(public_path($imagePath));
                }
            }

            $news->delete();

            return response()->json(['message' => 'News record deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete news record.'], 500);
        }
    }

    /**
 * Get news by category (public).
 */
public function getByCategory($category)
{
    try {
        $news = News::select(
            'news_id', 'category', 'description', 'news_img', 'pdf_file',
            'read_more_url_lnk', 'images', 'read_more_links', 'created_at', 'updated_at'
        )
        ->where('category', $category)
        ->orderBy('news_id', 'desc')
        ->get();

        if ($news->isEmpty()) {
            return response()->json(['message' => 'No news found for this category'], 404);
        }

        return response()->json(['news' => $news], 200);
    } catch (Exception $e) {
        Log::error('Error fetching news by category: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch news by category.'], 500);
    }
}

/**
 * Get all gallery images from all news records (public).
 * Returns a unique list of image URLs.
 */
public function getAllImages()
{
    try {
        // Fetch all news records and extract images
        $allNews = News::select('images')->get();
        $allImages = [];

        foreach ($allNews as $news) {
            if (!empty($news->images) && is_array($news->images)) {
                foreach ($news->images as $path) {
                    $allImages[] = asset($path);
                }
            }
        }

        // Remove duplicates and re-index
        $uniqueImages = array_unique($allImages);
        $uniqueImages = array_values($uniqueImages);

        return response()->json(['images' => $uniqueImages], 200);
    } catch (Exception $e) {
        Log::error('Error fetching all images: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch images.'], 500);
    }
}
}