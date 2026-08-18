<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestEvent', 'allEvents']);
    }

    /**
     * Helper: Upload a file and return relative path.
     */
    private function uploadFile($file, $directory = 'uploads/events')
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
     * Get validation rules (with new JSON fields).
     */
    private function getValidationRules()
    {
        return [
            'event_category' => 'required|string|max:255',
            'description'    => 'nullable|string',
            'img_file'       => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // legacy single image
            'video_link'     => 'nullable|url',                                  // legacy single video
            'images.*'       => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // multiple images
            'video_links'    => 'nullable|array',
            'video_links.*'  => 'nullable|url|max:500',
        ];
    }

    private function getSelectableFields()
    {
        return ['event_id', 'event_category', 'description', 'img_file', 'video_link', 'images', 'video_links', 'created_at', 'updated_at'];
    }

    // ---------- Public endpoints ----------
    public function index()
    {
        try {
            $events = Event::select($this->getSelectableFields())
                ->orderBy('event_id', 'desc')
                ->get();
            return response()->json(['events' => $events], 200);
        } catch (Exception $e) {
            Log::error('Error fetching events: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch events.'], 500);
        }
    }

    public function allEvents()
    {
        try {
            $events = Event::select($this->getSelectableFields())
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

    public function latestEvent()
    {
        try {
            $latest = Event::select($this->getSelectableFields())
                ->orderBy('event_id', 'asc')
                ->first();
            if (!$latest) {
                return response()->json(['message' => 'No event found'], 404);
            }
            return response()->json(['event' => $latest], 200);
        } catch (Exception $e) {
            Log::error('Error fetching latest event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest event.'], 500);
        }
    }

    public function show($event_id)
    {
        try {
            $event = Event::select($this->getSelectableFields())->find($event_id);
            if (!$event) {
                return response()->json(['message' => 'Event not found'], 404);
            }
            return response()->json(['event' => $event], 200);
        } catch (Exception $e) {
            Log::error('Error fetching event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch event.'], 500);
        }
    }

    public function getDropdownData()
    {
        try {
            $categories = Event::select('event_category')
                ->distinct()
                ->pluck('event_category')
                ->values();
            $events = Event::select('event_id', 'event_category')
                ->orderBy('event_id', 'asc')
                ->get()
                ->map(fn($e) => ['event_id' => $e->event_id, 'event_category' => $e->event_category]);
            return response()->json(['categories' => $categories, 'events' => $events], 200);
        } catch (Exception $e) {
            Log::error('Error fetching dropdown data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch dropdown data.'], 500);
        }
    }

    // ---------- Protected endpoints ----------
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Sanitize description (HTML)
            $data['description'] = clean($data['description'] ?? '');

            // Handle legacy single image
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                $data['img_file'] = $this->uploadFile($request->file('img_file'));
            }

            // Handle legacy single video link (if provided)
            // (no change needed, it's stored as is)

            // Handle multiple images
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image->isValid()) {
                        $imagePaths[] = $this->uploadFile($image);
                    }
                }
            }
            $data['images'] = $imagePaths;

            // Handle multiple video links (array from frontend)
            $data['video_links'] = $data['video_links'] ?? [];

            $event = Event::create($data);
            return response()->json(['message' => 'Event created successfully', 'event' => $event], 201);
        } catch (Exception $e) {
            Log::error('Error creating event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create event.'], 500);
        }
    }

    public function update(Request $request, $event_id)
    {
        $event = Event::find($event_id);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $validator = Validator::make($request->all(), $this->getValidationRules());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            // Sanitize description
            $data['description'] = clean($data['description'] ?? '');

            // ---- Legacy single image ----
            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                if ($event->img_file && File::exists(public_path($event->img_file))) {
                    File::delete(public_path($event->img_file));
                }
                $data['img_file'] = $this->uploadFile($request->file('img_file'));
            } else {
                $data['img_file'] = $event->img_file; // keep existing
            }

            // ---- Legacy single video link ----
            if ($request->filled('video_link')) {
                $data['video_link'] = $data['video_link'];
            } else {
                $data['video_link'] = $event->video_link;
            }

            // ---- Multiple images (JSON array) ----
            $existingImages = $event->images ?? [];

            // Remove images by index (if provided)
            if ($request->has('remove_image_indices')) {
                $indicesToRemove = $request->input('remove_image_indices');
                foreach ($indicesToRemove as $index) {
                    if (isset($existingImages[$index]) && File::exists(public_path($existingImages[$index]))) {
                        File::delete(public_path($existingImages[$index]));
                    }
                    unset($existingImages[$index]);
                }
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

            // ---- Multiple video links (JSON array) ----
            if ($request->has('video_links')) {
                $data['video_links'] = $data['video_links'] ?? [];
            } else {
                $data['video_links'] = $event->video_links;
            }

            $event->fill($data)->save();
            return response()->json(['message' => 'Event updated successfully.', 'event' => $event->fresh()], 200);
        } catch (Exception $e) {
            Log::error('Error updating event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update event.'], 500);
        }
    }

    public function destroy($event_id)
    {
        try {
            $event = Event::find($event_id);
            if (!$event) {
                return response()->json(['message' => 'Event not found'], 404);
            }

            // Delete legacy image
            if ($event->img_file && File::exists(public_path($event->img_file))) {
                File::delete(public_path($event->img_file));
            }

            // Delete all gallery images (from JSON array)
            $images = $event->images ?? [];
            foreach ($images as $path) {
                if (File::exists(public_path($path))) {
                    File::delete(public_path($path));
                }
            }

            $event->delete();
            return response()->json(['message' => 'Event deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error('Error deleting event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete event.'], 500);
        }
    }
}