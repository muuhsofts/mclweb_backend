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
     * Display all news records (ascending by news_id)
     */
    public function allNews()
    {
        try {
            DB::connection()->getPdo();
            if (!Schema::hasTable('news')) {
                \Log::error('Table news does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $newsRecords = News::select('news_id', 'category', 'description', 'news_img', 'pdf_file', 'read_more_url_lnk', 'created_at', 'updated_at')
                ->orderBy('news_id', 'asc')
                ->get();

            \Log::info('Successfully fetched all news records.', ['count' => $newsRecords->count()]);
            return response()->json(['news' => $newsRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching news records: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch news records.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Count total news records
     */
    public function countNews()
    {
        try {
            DB::connection()->getPdo();
            if (!Schema::hasTable('news')) {
                \Log::error('Table news does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $count = News::count();
            \Log::info('Successfully counted news records.', ['count' => $count]);
            return response()->json(['count_news' => $count], 200);
        } catch (Exception $e) {
            \Log::error('Error counting news records: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to count news records.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display news records (latest first by news_id)
     */
    public function index()
    {
        try {
            DB::connection()->getPdo();
            if (!Schema::hasTable('news')) {
                \Log::error('Table news does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $newsRecords = News::select('news_id', 'category', 'description', 'news_img', 'pdf_file', 'read_more_url_lnk', 'created_at', 'updated_at')
                ->orderBy('news_id', 'desc')
                ->get();

            \Log::info('Successfully fetched news records (index).', ['count' => $newsRecords->count()]);
            return response()->json(['news' => $newsRecords], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching news records: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch news records.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the latest news by created_at
     */
    public function latestnew()
    {
        try {
            DB::connection()->getPdo();
            if (!Schema::hasTable('news')) {
                \Log::error('Table news does not exist in the database.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $latestNews = News::select('news_id', 'category', 'description', 'news_img', 'pdf_file', 'read_more_url_lnk', 'created_at', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestNews) {
                \Log::warning('No news record found for latest request.');
                return response()->json(['message' => 'No news record found'], 404);
            }

            \Log::info('Successfully fetched latest news record.', ['news_id' => $latestNews->news_id]);
            return response()->json(['news' => $latestNews], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching latest news record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch latest news record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new news record
     */
    public function store(Request $request)
    {
        \Log::info('News store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'news_img' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'pdf_file' => 'nullable|file|mimes:pdf|max:2048',
            'read_more_url_lnk' => 'nullable|url|max:500', // Optional URL
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for news store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('news_img') && $request->file('news_img')->isValid()) {
                $uploadPath = public_path('uploads/news');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                    \Log::info('Created uploads/news directory.');
                }
                $file = $request->file('news_img');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['news_img'] = 'uploads/news/' . $fileName;
                \Log::info('Image uploaded: ' . $data['news_img']);
            }

            // Handle PDF upload
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                $uploadPath = public_path('uploads/news');
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }
                $file = $request->file('pdf_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['pdf_file'] = 'uploads/news/' . $fileName;
                \Log::info('PDF uploaded: ' . $data['pdf_file']);
            }

            $news = News::create($data);
            \Log::info('News record created.', ['news_id' => $news->news_id]);

            return response()->json([
                'message' => 'News record created successfully',
                'news' => $news->fresh(['read_more_url_lnk'])
            ], 201);
        } catch (Exception $e) {
            \Log::error('Error creating news record: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to create news record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Show news by ID (alias of show)
     */
    public function newsByid($news_id)
    {
        return $this->show($news_id);
    }

    /**
     * Display a single news record
     */
    public function show($news_id)
    {
        try {
            if (!Schema::hasTable('news')) {
                \Log::error('Table news does not exist.');
                return response()->json(['error' => 'Database table not found.'], 500);
            }

            $news = News::select('news_id', 'category', 'description', 'news_img', 'pdf_file', 'read_more_url_lnk', 'created_at', 'updated_at')
                ->find($news_id);

            if (!$news) {
                \Log::warning('News record not found: ' . $news_id);
                return response()->json(['message' => 'News record not found'], 404);
            }

            \Log::info('Fetched news record.', ['news_id' => $news_id]);
            return response()->json(['news' => $news], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update news record
     */
    public function update(Request $request, $news_id)
    {
        \Log::info("Update request for news_id {$news_id}: ", $request->all());

        try {
            $news = News::find($news_id);
            if (!$news) {
                \Log::warning("News not found: {$news_id}");
                return response()->json(['message' => 'News record not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:255',
                'description' => 'nullable|string',
                'news_img' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
                'pdf_file' => 'nullable|file|mimes:pdf|max:2048',
                'read_more_url_lnk' => 'nullable|url|max:500',
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation failed on update.', $validator->errors()->toArray());
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            // Handle image replacement
            if ($request->hasFile('news_img') && $request->file('news_img')->isValid()) {
                if ($news->news_img && File::exists(public_path($news->news_img))) {
                    File::delete(public_path($news->news_img));
                    \Log::info('Deleted old image: ' . $news->news_img);
                }
                $uploadPath = public_path('uploads/news');
                if (!File::exists($uploadPath)) File::makeDirectory($uploadPath, 0755, true);
                $file = $request->file('news_img');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['news_img'] = 'uploads/news/' . $fileName;
                \Log::info('New image uploaded: ' . $data['news_img']);
            } else {
                $data['news_img'] = $news->news_img;
            }

            // Handle PDF replacement
            if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
                if ($news->pdf_file && File::exists(public_path($news->pdf_file))) {
                    File::delete(public_path($news->pdf_file));
                    \Log::info('Deleted old PDF: ' . $news->pdf_file);
                }
                $uploadPath = public_path('uploads/news');
                if (!File::exists($uploadPath)) File::makeDirectory($uploadPath, 0755, true);
                $file = $request->file('pdf_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $fileName);
                $data['pdf_file'] = 'uploads/news/' . $fileName;
                \Log::info('New PDF uploaded: ' . $data['pdf_file']);
            } else {
                $data['pdf_file'] = $news->pdf_file;
            }

            // Preserve read_more_url_lnk if not sent
            $data['read_more_url_lnk'] = $request->filled('read_more_url_lnk') ? $data['read_more_url_lnk'] : $news->read_more_url_lnk;

            $news->fill($data)->save();
            \Log::info("News updated successfully: {$news_id}");

            return response()->json([
                'message' => 'News record updated successfully.',
                'news' => $news->fresh()
            ], 200);
        } catch (Exception $e) {
            \Log::error('Update failed: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to update news record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete news record
     */
    public function destroy($news_id)
    {
        try {
            $news = News::find($news_id);
            if (!$news) {
                \Log::warning("Delete failed - news not found: {$news_id}");
                return response()->json(['message' => 'News record not found'], 404);
            }

            // Delete files
            if ($news->news_img && File::exists(public_path($news->news_img))) {
                File::delete(public_path($news->news_img));
                \Log::info('Deleted image: ' . $news->news_img);
            }
            if ($news->pdf_file && File::exists(public_path($news->pdf_file))) {
                File::delete(public_path($news->pdf_file));
                \Log::info('Deleted PDF: ' . $news->pdf_file);
            }

            $news->delete();
            \Log::info("News deleted: {$news_id}");

            return response()->json(['message' => 'News record deleted successfully'], 200);
        } catch (Exception $e) {
            \Log::error('Delete failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete news record.', 'details' => $e->getMessage()], 500);
        }
    }
}