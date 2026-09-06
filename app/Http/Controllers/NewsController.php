<?php
// app/Http/Controllers/NewsController.php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\ContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class NewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'index', 'show', 'latestnew', 'allNews', 'newsByid', 'countNews', 'downloadPdf'
        ]);
    }

    private function uploadImage($file, $directory = 'uploads/featured')
    {
        $path = public_path($directory);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($path, $name);
        return $directory . '/' . $name;
    }

    private function uploadPdf($file, $directory = 'uploads/pdfs')
    {
        $path = public_path($directory);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($path, $name);
        return $directory . '/' . $name;
    }

    // ---------- PUBLIC ----------

    public function index()
    {
        try {
            $news = News::with('contentBlocks')->orderBy('news_id', 'desc')->get();
            return response()->json(['news' => $news], 200);
        } catch (Exception $e) {
            Log::error('Error fetching news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news.'], 500);
        }
    }

    public function show($news_id)
    {
        try {
            $news = News::with('contentBlocks')->find($news_id);
            if (!$news) {
                return response()->json(['message' => 'News not found'], 404);
            }
            return response()->json(['news' => $news], 200);
        } catch (Exception $e) {
            Log::error('Error fetching news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news.'], 500);
        }
    }

    public function latestnew()
{
    try {
        $news = News::with('contentBlocks')
            ->where('status', '!=', 'draft')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$news) {
            return response()->json([
                'message' => 'No published news found'
            ], 404);
        }

        return response()->json([
            'news' => $news
        ], 200);

    } catch (Exception $e) {
        Log::error('Error fetching latest news: ' . $e->getMessage());

        return response()->json([
            'error' => 'Failed to fetch latest news.'
        ], 500);
    }
}



    public function allNews()
{
    try {
        $news = News::where('status', 'published')
            ->select('news_id', 'title', 'slug', 'featured_image', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['news' => $news], 200);
    } catch (Exception $e) {
        Log::error('Error fetching optimized news: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch news.'], 500);
    }
}

    public function newsByid($news_id)
    {
        return $this->show($news_id);
    }

    public function countNews()
    {
        try {
            $count = News::count();
            return response()->json(['count_news' => $count], 200);
        } catch (Exception $e) {
            Log::error('Error counting news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count news.'], 500);
        }
    }

    // ---------- PDF DOWNLOAD ----------
    public function downloadPdf($news_id)
    {
        try {
            $news = News::find($news_id);
            if (!$news) {
                return response()->json(['message' => 'News not found'], 404);
            }

            if (!$news->pdf_file || !File::exists(public_path($news->pdf_file))) {
                return response()->json(['message' => 'PDF file not found'], 404);
            }

            return response()->download(public_path($news->pdf_file));
        } catch (Exception $e) {
            Log::error('Error downloading PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to download PDF.'], 500);
        }
    }

    // ---------- PROTECTED ----------

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'featured_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'pdf_file'       => 'nullable|file|mimes:pdf|max:10240',
            'status'         => 'nullable|in:draft,published',
            'published_at'   => 'nullable|date',
            'blocks'         => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->uploadImage($request->file('featured_image'), 'uploads/featured');
        }

        if ($request->hasFile('pdf_file')) {
            $data['pdf_file'] = $this->uploadPdf($request->file('pdf_file'), 'uploads/pdfs');
        }

        if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Remove blocks from mass-assignment data
        unset($data['blocks']);

        $news = News::create($data);

        $blocks = json_decode($request->input('blocks', '[]'), true);
        if (is_array($blocks) && count($blocks) > 0) {
            $this->syncBlocks($news, $blocks, $request);
        }

        Log::info('News created', ['news_id' => $news->news_id, 'blocks' => count($blocks ?? [])]);

        return response()->json([
            'message' => 'News created successfully',
            'news'    => $news->load('contentBlocks')
        ], 201);
    }

    public function update(Request $request, $news_id)
    {
        try {
            $news = News::find($news_id);
            if (!$news) {
                return response()->json(['message' => 'News not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'title'           => 'sometimes|required|string|max:255',
                'featured_image'  => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'pdf_file'        => 'nullable|file|mimes:pdf|max:10240',
                'status'          => 'nullable|in:draft,published',
                'published_at'    => 'nullable|date',
                'remove_featured' => 'nullable|boolean',
                'remove_pdf'      => 'nullable|boolean',
                'blocks'          => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();
            unset($data['blocks']);

            // Handle featured image removal
            if ($request->boolean('remove_featured') && $news->featured_image) {
                if (File::exists(public_path($news->featured_image))) {
                    File::delete(public_path($news->featured_image));
                }
                $data['featured_image'] = null;
            }

            // Handle PDF removal
            if ($request->boolean('remove_pdf') && $news->pdf_file) {
                if (File::exists(public_path($news->pdf_file))) {
                    File::delete(public_path($news->pdf_file));
                }
                $data['pdf_file'] = null;
            }

            // Upload new featured image
            if ($request->hasFile('featured_image')) {
                if ($news->featured_image && File::exists(public_path($news->featured_image))) {
                    File::delete(public_path($news->featured_image));
                }
                $data['featured_image'] = $this->uploadImage($request->file('featured_image'), 'uploads/featured');
            }

            // Upload new PDF
            if ($request->hasFile('pdf_file')) {
                if ($news->pdf_file && File::exists(public_path($news->pdf_file))) {
                    File::delete(public_path($news->pdf_file));
                }
                $data['pdf_file'] = $this->uploadPdf($request->file('pdf_file'), 'uploads/pdfs');
            }

            if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $news->fill($data)->save();

            $blocks = json_decode($request->input('blocks', '[]'), true);
            if (is_array($blocks)) {
                $this->syncBlocks($news, $blocks, $request);
            }

            Log::info('News updated', ['news_id' => $news->news_id]);

            return response()->json([
                'message' => 'News updated successfully',
                'news'    => $news->load('contentBlocks')
            ], 200);

        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error'  => 'Failed to update news.',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Replace all content blocks. Supports:
     * - text (content)
     * - single url
     * - multiple images (new uploads via block_images[index][] + existing paths)
     * - caption
     */
    private function syncBlocks($news, array $blocks, Request $request)
    {
        // 1. Delete old blocks + their files
        foreach ($news->contentBlocks as $block) {
            $this->deleteBlockFiles($block);
            $block->delete();
        }

        // 2. Get all uploaded block images at once (robust)
        $allBlockImages = $request->file('block_images') ?? [];

        $order = 0;
        foreach ($blocks as $index => $blockData) {
            $imagePaths = [];

            // A. New uploads for this block index
            $files = $allBlockImages[$index] ?? [];
            if (!is_array($files)) {
                $files = $files ? [$files] : [];
            }
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $imagePaths[] = $this->uploadImage($file, 'uploads/blocks');
                }
            }

            // B. Keep existing paths that frontend still wants
            if (!empty($blockData['image_paths']) && is_array($blockData['image_paths'])) {
                foreach ($blockData['image_paths'] as $path) {
                    if ($path && is_string($path)) {
                        $imagePaths[] = $path;
                    }
                }
            }

            // C. Backward-compat single image_path
            if (empty($imagePaths) && !empty($blockData['image_path'])) {
                $imagePaths[] = $blockData['image_path'];
            }

            // D. Deduplicate & clean
            $imagePaths = array_values(array_unique(array_filter($imagePaths)));

            $fields = [
                'news_id'     => $news->news_id,
                'type'        => 'mixed',
                'block_order' => $order++,
                'content'     => $blockData['content'] ?? null,
                'url'         => !empty($blockData['url']) ? trim($blockData['url']) : null,
                'caption'     => $blockData['caption'] ?? null,
                'image_paths' => !empty($imagePaths) ? $imagePaths : null,
                // keep old column null
                'image_path'  => null,
            ];

            ContentBlock::create($fields);
        }
    }

    private function deleteBlockFiles($block)
    {
        if ($block->image_paths && is_array($block->image_paths)) {
            foreach ($block->image_paths as $path) {
                if ($path && File::exists(public_path($path))) {
                    File::delete(public_path($path));
                }
            }
        }
        if ($block->image_path && File::exists(public_path($block->image_path))) {
            File::delete(public_path($block->image_path));
        }
    }

    public function destroy($news_id)
    {
        try {
            $news = News::find($news_id);
            if (!$news) {
                return response()->json(['message' => 'News not found'], 404);
            }

            // Delete featured image
            if ($news->featured_image && File::exists(public_path($news->featured_image))) {
                File::delete(public_path($news->featured_image));
            }

            // Delete PDF file
            if ($news->pdf_file && File::exists(public_path($news->pdf_file))) {
                File::delete(public_path($news->pdf_file));
            }

            // Delete content blocks and their files
            foreach ($news->contentBlocks as $block) {
                $this->deleteBlockFiles($block);
            }

            $news->delete();

            Log::info('News deleted', ['news_id' => $news_id]);
            return response()->json(['message' => 'News deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete news.'], 500);
        }
    }

    public function uploadBlockImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $this->uploadImage($request->file('image'), 'uploads/blocks');

        return response()->json([
            'location' => asset($path),
            'path'     => $path,
        ], 200);
    }
}