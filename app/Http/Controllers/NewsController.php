<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\ContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class NewsController extends Controller
{
    public function __construct()
    {
        // Public methods: index, show, latestnew, allNews, newsByid
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestnew', 'allNews', 'newsByid']);
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

    /**
     * Get all news (latest first) with their content blocks
     */
    public function index()
    {
        try {
            $news = News::with('contentBlocks')->orderBy('news_id', 'desc')->get();
            return response()->json(['news' => $news], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all news: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch news.'], 500);
        }
    }

    /**
     * Get a single news item with its blocks
     */
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

    /**
     * Get the latest news (with blocks)
     */
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

    /**
     * Get all news in ascending order (for admin listing)
     */
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

    /**
     * Alias for show()
     */
    public function newsByid($news_id)
    {
        return $this->show($news_id);
    }

    // ---------- PROTECTED (AUTH) ENDPOINTS ----------

    /**
     * Store a new news post with content blocks
     * Expects 'blocks' as a JSON string.
     */
    public function store(Request $request)
    {
        // Validate request – blocks is a JSON string
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'featured_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status'         => 'nullable|in:draft,published',
            'published_at'   => 'nullable|date',
            'blocks'         => 'nullable|json', // accept JSON string
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Handle featured image
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->uploadImage($request->file('featured_image'), 'uploads/featured');
        }

        // Auto‑set published_at if status is published
        if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Create the news post
        $news = News::create($data);

        // Decode blocks from JSON string to array
        $blocks = json_decode($data['blocks'] ?? '[]', true);
        if (!is_array($blocks)) {
            $blocks = [];
        }

        // Log the blocks for debugging
        Log::info('Creating news with blocks:', ['news_id' => $news->news_id, 'blocks' => $blocks]);

        // Process blocks if provided
        if (!empty($blocks)) {
            $this->syncBlocks($news, $blocks, $request);
        }

        return response()->json([
            'message' => 'News created successfully',
            'news'    => $news->load('contentBlocks')
        ], 201);
    }

    /**
     * Update an existing news post
     * Expects 'blocks' as a JSON string.
     */
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
                'blocks'         => 'nullable|json', // accept JSON string
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            // Update featured image
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

            // Decode blocks from JSON string
            $blocks = json_decode($data['blocks'] ?? '[]', true);
            if (!is_array($blocks)) {
                $blocks = [];
            }

            Log::info('Updating news with blocks:', ['news_id' => $news->news_id, 'blocks' => $blocks]);

            if (!empty($blocks)) {
                $this->syncBlocks($news, $blocks, $request);
            }

            return response()->json([
                'message' => 'News updated successfully',
                'news'    => $news->load('contentBlocks')
            ], 200);
        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update news.'], 500);
        }
    }

    /**
     * Helper to sync content blocks (create/update/delete)
     * The $blocks array is already decoded from JSON.
     */
    private function syncBlocks($news, $blocks, $request)
    {
        $existingBlockIds = [];
        $order = 0;

        foreach ($blocks as $blockData) {
            // If block has 'remove' flag, delete it
            if (isset($blockData['remove']) && $blockData['remove'] && isset($blockData['id'])) {
                $block = ContentBlock::find($blockData['id']);
                if ($block && $block->news_id == $news->news_id) {
                    $this->deleteBlockFiles($block);
                    $block->delete();
                }
                continue;
            }

            // Prepare block fields
            $blockFields = [
                'block_order' => $order++,
                'type'        => $blockData['type'] ?? 'text',
                'content'     => $blockData['content'] ?? null,
                'caption'     => $blockData['caption'] ?? null,
            ];

            // Handle image upload for 'image' type
            if (isset($blockData['type']) && $blockData['type'] === 'image') {
                // If a file is provided in the request (standalone image block)
                // We expect an uploaded file with key 'block_images[<index>]'
                $imageIndex = $order - 1; // because we incremented $order
                if ($request->hasFile("block_images.{$imageIndex}")) {
                    $file = $request->file("block_images.{$imageIndex}");
                    // If updating existing block, delete old image
                    if (isset($blockData['id'])) {
                        $oldBlock = ContentBlock::find($blockData['id']);
                        if ($oldBlock && $oldBlock->news_id == $news->news_id && $oldBlock->image_path) {
                            if (File::exists(public_path($oldBlock->image_path))) {
                                File::delete(public_path($oldBlock->image_path));
                            }
                        }
                    }
                    $blockFields['image_path'] = $this->uploadImage($file, 'uploads/blocks');
                } elseif (isset($blockData['id'])) {
                    // Keep existing image if not replaced
                    $oldBlock = ContentBlock::find($blockData['id']);
                    if ($oldBlock) {
                        $blockFields['image_path'] = $oldBlock->image_path;
                    }
                }
            }

            // Update or create
            if (isset($blockData['id'])) {
                $block = ContentBlock::find($blockData['id']);
                if ($block && $block->news_id == $news->news_id) {
                    $block->fill($blockFields)->save();
                    $existingBlockIds[] = $block->id;
                }
            } else {
                $block = new ContentBlock($blockFields);
                $block->news_id = $news->news_id;
                $block->save();
                $existingBlockIds[] = $block->id;
            }
        }

        // Delete any blocks not in the updated list (if we want full replacement, uncomment next line)
        // ContentBlock::where('news_id', $news->news_id)->whereNotIn('id', $existingBlockIds)->delete();
    }

    /**
     * Delete files associated with a block
     */
    private function deleteBlockFiles($block)
    {
        if ($block->image_path && File::exists(public_path($block->image_path))) {
            File::delete(public_path($block->image_path));
        }
    }

    /**
     * Delete a news post and all its blocks & files
     */
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

            // Delete all block images
            foreach ($news->contentBlocks as $block) {
                $this->deleteBlockFiles($block);
            }

            $news->delete();

            return response()->json(['message' => 'News deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete news.'], 500);
        }
    }

    /**
     * Upload an image for a content block (standalone endpoint)
     * Returns the URL so frontend can insert into blocks array.
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