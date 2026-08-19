<?php

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
            'index', 'show', 'latestnew', 'allNews', 'newsByid'
        ]);
    }

    /**
     * Upload helper for images (featured or block images)
     */
    private function uploadImage($file, $directory = 'uploads/featured')
    {
        $path = public_path($directory);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $name = time() . '_' . $file->getClientOriginalName();
        $file->move($path, $name);
        return $directory . '/' . $name;
    }

    // ---------- PUBLIC ENDPOINTS ----------

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
            $news = News::with('contentBlocks')->orderBy('created_at', 'desc')->first();
            if (!$news) {
                return response()->json(['message' => 'No news found'], 404);
            }
            return response()->json(['news' => $news], 200);
        } catch (Exception $e) {
            Log::error('Error fetching latest news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest news.'], 500);
        }
    }

    public function allNews()
    {
        try {
            $news = News::with('contentBlocks')->orderBy('news_id', 'asc')->get();
            return response()->json(['news' => $news], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all news (asc): ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news.'], 500);
        }
    }

    public function newsByid($news_id)
    {
        return $this->show($news_id);
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
            Log::error('Error counting news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count news.'], 500);
        }
    }

    // ---------- PROTECTED (AUTH) ENDPOINTS ----------

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'featured_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
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

        if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $news = News::create($data);

        $blocks = json_decode($data['blocks'] ?? '[]', true);
        if (is_array($blocks) && !empty($blocks)) {
            $this->syncBlocks($news, $blocks, $request);
        }

        Log::info('News created', ['news_id' => $news->news_id, 'blocks' => count($blocks)]);

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
                'title'          => 'sometimes|required|string|max:255',
                'featured_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'status'         => 'nullable|in:draft,published',
                'published_at'   => 'nullable|date',
                'remove_featured' => 'nullable|boolean',
                'blocks'         => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            // Featured image handling
            if ($request->input('remove_featured', false) && $news->featured_image) {
                if (File::exists(public_path($news->featured_image))) {
                    File::delete(public_path($news->featured_image));
                }
                $data['featured_image'] = null;
            }

            if ($request->hasFile('featured_image')) {
                if ($news->featured_image && File::exists(public_path($news->featured_image))) {
                    File::delete(public_path($news->featured_image));
                }
                $data['featured_image'] = $this->uploadImage($request->file('featured_image'), 'uploads/featured');
            }

            if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $news->fill($data)->save();

            $blocks = json_decode($data['blocks'] ?? '[]', true);
            if (is_array($blocks) && !empty($blocks)) {
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
                'error' => 'Failed to update news.',
                'detail' => $e->getMessage() // temporarily for debugging – remove in production
            ], 500);
        }
    }

    /**
     * Synchronise content blocks (create/update/delete)
     */
    private function syncBlocks($news, $blocks, $request)
    {
        $existingIds = [];
        $order = 0;

        foreach ($blocks as $blockData) {
            // Delete flagged blocks
            if (isset($blockData['remove']) && $blockData['remove'] && isset($blockData['id'])) {
                $block = ContentBlock::find($blockData['id']);
                if ($block && $block->news_id == $news->news_id) {
                    $this->deleteBlockFiles($block);
                    $block->delete();
                }
                continue;
            }

            $fields = [
                'block_order' => $order++,
                'type'        => $blockData['type'] ?? 'text',
                'content'     => $blockData['content'] ?? null,
                'caption'     => $blockData['caption'] ?? null,
            ];

            // Handle image uploads for image blocks
            if (isset($blockData['type']) && $blockData['type'] === 'image') {
                $index = $order - 1; // current block index
                if ($request->hasFile("block_images.{$index}")) {
                    $file = $request->file("block_images.{$index}");
                    if (isset($blockData['id'])) {
                        $old = ContentBlock::find($blockData['id']);
                        if ($old && $old->news_id == $news->news_id && $old->image_path) {
                            if (File::exists(public_path($old->image_path))) {
                                File::delete(public_path($old->image_path));
                            }
                        }
                    }
                    $fields['image_path'] = $this->uploadImage($file, 'uploads/blocks');
                } elseif (isset($blockData['id'])) {
                    // Preserve existing image
                    $old = ContentBlock::find($blockData['id']);
                    if ($old) {
                        $fields['image_path'] = $old->image_path;
                    }
                }
            }

            // Create or update
            if (isset($blockData['id'])) {
                $block = ContentBlock::find($blockData['id']);
                if ($block && $block->news_id == $news->news_id) {
                    $block->fill($fields)->save();
                    $existingIds[] = $block->id;
                }
            } else {
                $block = new ContentBlock($fields);
                $block->news_id = $news->news_id;
                $block->save();
                $existingIds[] = $block->id;
            }
        }

        // Optional: remove blocks not in $existingIds (if you want full replacement, uncomment)
        // ContentBlock::where('news_id', $news->news_id)->whereNotIn('id', $existingIds)->delete();
    }

    /**
     * Delete files attached to a content block
     */
    private function deleteBlockFiles($block)
    {
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

            if ($news->featured_image && File::exists(public_path($news->featured_image))) {
                File::delete(public_path($news->featured_image));
            }

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

    /**
     * Upload an image for a content block (standalone)
     */
    public function uploadBlockImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
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