<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'index', 'show', 'latestEvent', 'allEvents', 'countEvents'
        ]);
    }

    private function uploadFile($file, $directory = 'uploads/events')
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
            $events = Event::with('contentBlocks')
                ->orderBy('event_id', 'desc')
                ->get();
            return response()->json(['events' => $events], 200);
        } catch (Exception $e) {
            Log::error('Error fetching events: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch events.'], 500);
        }
    }

    public function show($event_id)
    {
        try {
            $event = Event::with('contentBlocks')->find($event_id);
            if (!$event) {
                return response()->json(['message' => 'Event not found'], 404);
            }
            return response()->json(['event' => $event], 200);
        } catch (Exception $e) {
            Log::error('Error fetching event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch event.'], 500);
        }
    }

    public function latestEvent()
    {
        try {
            $event = Event::with('contentBlocks')
                ->orderBy('created_at', 'desc')
                ->first();
            if (!$event) {
                return response()->json(['message' => 'No event found'], 404);
            }
            return response()->json(['event' => $event], 200);
        } catch (Exception $e) {
            Log::error('Error fetching latest event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest event.'], 500);
        }
    }

    public function allEvents()
    {
        try {
            $events = Event::with('contentBlocks')
                ->orderBy('event_id', 'asc')
                ->get();
            return response()->json(['events' => $events], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all events: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch all events.'], 500);
        }
    }

    public function countEvents()
    {
        try {
            $count = Event::count();
            return response()->json(['count_events' => $count], 200);
        } catch (Exception $e) {
            Log::error('Error counting events: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count events.'], 500);
        }
    }

    // ---------- PROTECTED ----------

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

        $data = $validator->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->uploadFile($request->file('featured_image'), 'uploads/events/featured');
        }

        if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        unset($data['blocks']);

        $event = Event::create($data);

        $blocks = json_decode($request->input('blocks', '[]'), true);
        if (is_array($blocks) && count($blocks) > 0) {
            $this->syncBlocks($event, $blocks, $request);
        }

        Log::info('Event created', ['event_id' => $event->event_id, 'blocks' => count($blocks ?? [])]);

        return response()->json([
            'message' => 'Event created successfully',
            'event'   => $event->load('contentBlocks')
        ], 201);
    }

    public function update(Request $request, $event_id)
    {
        try {
            $event = Event::find($event_id);
            if (!$event) {
                return response()->json(['message' => 'Event not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'title'          => 'sometimes|required|string|max:255',
                'featured_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'status'         => 'nullable|in:draft,published',
                'published_at'   => 'nullable|date',
                'remove_featured' => 'nullable|boolean',
                'blocks'         => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();
            unset($data['blocks']);

            if ($request->boolean('remove_featured') && $event->featured_image) {
                if (File::exists(public_path($event->featured_image))) {
                    File::delete(public_path($event->featured_image));
                }
                $data['featured_image'] = null;
            }

            if ($request->hasFile('featured_image')) {
                if ($event->featured_image && File::exists(public_path($event->featured_image))) {
                    File::delete(public_path($event->featured_image));
                }
                $data['featured_image'] = $this->uploadFile($request->file('featured_image'), 'uploads/events/featured');
            }

            if (isset($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
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
                'event'   => $event->load('contentBlocks')
            ], 200);

        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error'  => 'Failed to update event.',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    private function syncBlocks($event, array $blocks, Request $request)
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

            $fields = [
                'event_id'    => $event->event_id,
                'type'        => 'mixed',
                'block_order' => $order++,
                'content'     => $blockData['content'] ?? null,
                'url'         => !empty($blockData['url']) ? trim($blockData['url']) : null,
                'caption'     => $blockData['caption'] ?? null,
                'image_paths' => !empty($imagePaths) ? $imagePaths : null,
            ];

            EventContentBlock::create($fields);
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

    public function destroy($event_id)
    {
        try {
            $event = Event::find($event_id);
            if (!$event) {
                return response()->json(['message' => 'Event not found'], 404);
            }

            if ($event->featured_image && File::exists(public_path($event->featured_image))) {
                File::delete(public_path($event->featured_image));
            }

            foreach ($event->contentBlocks as $block) {
                $this->deleteBlockFiles($block);
            }

            $event->delete();

            Log::info('Event deleted', ['event_id' => $event_id]);
            return response()->json(['message' => 'Event deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete event.'], 500);
        }
    }
}