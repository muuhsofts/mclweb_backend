<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'index', 'show', 'latestEvent', 'allEvents', 'countEvents',
        ]);
    }

    // ============================================================
    // PUBLIC
    // ============================================================

    public function index()
    {
        return $this->safeCall(function () {
            $events = Event::with('contentBlocks')
                ->orderBy('event_id', 'desc')
                ->get();

            return response()->json(['events' => $events], 200);
        }, 'Failed to fetch events.');
    }

    public function show($event_id)
    {
        return $this->safeCall(function () use ($event_id) {
            $event = Event::with('contentBlocks')
                ->where('event_id', (int) $event_id)
                ->first();

            if (!$event) {
                Log::info("Event not found for event_id={$event_id}");
                return response()->json(['message' => 'Event not found'], 404);
            }

            return response()->json(['event' => $event], 200);
        }, 'Failed to fetch event.');
    }

    public function latestEvent()
    {
        return $this->safeCall(function () {
            $event = Event::with('contentBlocks')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$event) {
                return response()->json(['message' => 'No event found'], 404);
            }

            return response()->json(['event' => $event], 200);
        }, 'Failed to fetch latest event.');
    }

    public function allEvents()
    {
        return $this->safeCall(function () {
            $events = Event::with('contentBlocks')
                ->orderBy('event_id', 'asc')
                ->get();

            return response()->json(['events' => $events], 200);
        }, 'Failed to fetch all events.');
    }

    public function countEvents()
    {
        return $this->safeCall(function () {
            return response()->json(['count_events' => Event::count()], 200);
        }, 'Failed to count events.');
    }

    /**
     * Lightweight list for admin dropdowns: id + title only.
     * (Route existed in api.php but was missing from the controller.)
     */
    public function getDropdownData()
    {
        return $this->safeCall(function () {
            $events = Event::orderBy('event_id', 'desc')
                ->get(['event_id', 'title']);

            return response()->json(['events' => $events], 200);
        }, 'Failed to fetch event dropdown data.');
    }

    // ============================================================
    // PROTECTED
    // ============================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'featured_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'status'         => 'nullable|in:draft,published',
            'published_at'   => 'nullable|date',
            'blocks'         => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return $this->safeCall(function () use ($request, $validator) {
            $data = $validator->validated();
            unset($data['blocks']);

            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $this->uploadFile(
                    $request->file('featured_image'),
                    'uploads/events/featured'
                );
            }

            if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $event = Event::create($data);

            $blocks = json_decode($request->input('blocks', '[]'), true);
            if (is_array($blocks) && count($blocks) > 0) {
                $this->syncBlocks($event, $blocks, $request);
            }

            Log::info('Event created', ['event_id' => $event->event_id, 'blocks' => count($blocks ?? [])]);

            return response()->json([
                'message' => 'Event created successfully',
                'event'   => $event->load('contentBlocks'),
            ], 201);
        }, 'Failed to create event.');
    }

    public function update(Request $request, $event_id)
    {
        $event = Event::where('event_id', (int) $event_id)->first();
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'sometimes|required|string|max:255',
            'featured_image'  => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'status'          => 'nullable|in:draft,published',
            'published_at'    => 'nullable|date',
            'remove_featured' => 'nullable|boolean',
            'blocks'          => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return $this->safeCall(function () use ($request, $event, $validator) {
            $data = $validator->validated();
            unset($data['blocks']);

            if ($request->boolean('remove_featured') && $event->featured_image) {
                $this->deleteStoredFile($event->featured_image);
                $data['featured_image'] = null;
            }

            if ($request->hasFile('featured_image')) {
                if ($event->featured_image) {
                    $this->deleteStoredFile($event->featured_image);
                }
                $data['featured_image'] = $this->uploadFile(
                    $request->file('featured_image'),
                    'uploads/events/featured'
                );
            }

            if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $event->fill($data)->save();

            $blocks = json_decode($request->input('blocks', '[]'), true);
            if (is_array($blocks)) {
                $this->syncBlocks($event, $blocks, $request);
            }

            Log::info('Event updated', ['event_id' => $event->event_id]);

            return response()->json([
                'message' => 'Event updated successfully',
                'event'   => $event->load('contentBlocks'),
            ], 200);
        }, 'Failed to update event.');
    }

    public function destroy($event_id)
    {
        $event = Event::where('event_id', (int) $event_id)->first();
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return $this->safeCall(function () use ($event, $event_id) {
            if ($event->featured_image) {
                $this->deleteStoredFile($event->featured_image);
            }

            foreach ($event->contentBlocks as $block) {
                $this->deleteBlockFiles($block);
            }

            $event->delete();

            Log::info('Event deleted', ['event_id' => $event_id]);

            return response()->json(['message' => 'Event deleted successfully'], 200);
        }, 'Failed to delete event.');
    }

    // ============================================================
    // INTERNAL HELPERS
    // ============================================================

    /**
     * Runs $callback, converting any exception into a consistent
     * JSON error response and logging the trace.
     */
    private function safeCall(callable $callback, string $errorMessage)
    {
        try {
            return $callback();
        } catch (Exception $e) {
            Log::error($errorMessage . ' ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error'  => $errorMessage,
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    private function uploadFile($file, string $directory = 'uploads/events'): string
    {
        $path = public_path($directory);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($path, $name);

        return $directory . '/' . $name;
    }

    private function deleteStoredFile(?string $relativePath): void
    {
        if ($relativePath && File::exists(public_path($relativePath))) {
            File::delete(public_path($relativePath));
        }
    }

    private function syncBlocks(Event $event, array $blocks, Request $request): void
    {
        foreach ($event->contentBlocks as $block) {
            $this->deleteBlockFiles($block);
            $block->delete();
        }

        $allBlockImages = $request->file('block_images') ?? [];
        $order = 0;

        foreach ($blocks as $index => $blockData) {
            $imagePaths = [];

            $files = $allBlockImages[$index] ?? [];
            if (!is_array($files)) {
                $files = $files ? [$files] : [];
            }
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $imagePaths[] = $this->uploadFile($file, 'uploads/events/blocks');
                }
            }

            if (!empty($blockData['image_paths']) && is_array($blockData['image_paths'])) {
                foreach ($blockData['image_paths'] as $path) {
                    if ($path && is_string($path)) {
                        $imagePaths[] = $path;
                    }
                }
            }

            if (empty($imagePaths) && !empty($blockData['image_path'])) {
                $imagePaths[] = $blockData['image_path'];
            }

            $imagePaths = array_values(array_unique(array_filter($imagePaths)));

            EventContentBlock::create([
                'event_id'    => $event->event_id,
                'type'        => 'mixed',
                'block_order' => $order++,
                'content'     => $blockData['content'] ?? null,
                'url'         => !empty($blockData['url']) ? trim($blockData['url']) : null,
                'caption'     => $blockData['caption'] ?? null,
                'image_paths' => !empty($imagePaths) ? $imagePaths : null,
            ]);
        }
    }

    private function deleteBlockFiles($block): void
    {
        if ($block->image_paths && is_array($block->image_paths)) {
            foreach ($block->image_paths as $path) {
                $this->deleteStoredFile($path);
            }
        }

        if (!empty($block->image_path)) {
            $this->deleteStoredFile($block->image_path);
        }
    }
}