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
     * Helper to get the validation rules.
     */
    private function getValidationRules()
    {
        return [
            'event_category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'img_file' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max for image
            // highlight-start
            // The regex for YouTube has been removed. Now it just needs to be a valid URL.
            'video_link' => 'nullable|url',
            // highlight-end
        ];
    }

    /**
     * Helper to get selectable fields.
     */
    private function getSelectableFields()
    {
        return ['event_id', 'event_category', 'description', 'img_file', 'video_link', 'created_at', 'updated_at'];
    }

    /**
     * Display a listing of event records in descending order.
     */
    public function index()
    {
        try {
            $eventRecords = Event::select($this->getSelectableFields())
                ->orderBy('event_id', 'desc')
                ->get();
            return response()->json(['events' => $eventRecords], 200);
        } catch (Exception $e) {
            Log::error('Error fetching event records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch event records.'], 500);
        }
    }

   /**
 * Display all event records in ascending order.
 */
public function allEvents()
{
    try {
        $eventRecords = Event::select($this->getSelectableFields())
            ->orderBy('event_id', 'asc')
            ->get();
        return response()->json(['events' => $eventRecords], 200);
    } catch (Exception $e) {
        Log::error('Error fetching all event records: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch all event records.'], 500);
    }
}

    /**
     * Count the total number of event records.
     */
    public function countEvents()
    {
        try {
            $count = Event::count();
            return response()->json(['count_events' => $count], 200);
        } catch (Exception $e) {
            Log::error('Error counting event records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count event records.'], 500);
        }
    }

    /**
     * Display the latest event record based on created_at.
     */
    public function latestEvent()
    {
        try {
            $latestEvent = Event::select($this->getSelectableFields())
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$latestEvent) {
                return response()->json(['message' => 'No event record found'], 404);
            }
            return response()->json(['event' => $latestEvent], 200);
        } catch (Exception $e) {
            Log::error('Error fetching latest event record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest event record.'], 500);
        }
    }

    /**
     * Store a newly created event record.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                $file = $request->file('img_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/events'), $fileName);
                $data['img_file'] = 'uploads/events/' . $fileName;
            }

            $event = Event::create($data);
            return response()->json(['message' => 'Event record created successfully', 'event' => $event], 201);
        } catch (Exception $e) {
            Log::error('Error creating event record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create event record.'], 500);
        }
    }

 /**
     * Fetch event IDs and categories for dropdowns.
     */
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
            ->map(function ($event) {
                return [
                    'event_id' => $event->event_id,
                    'event_category' => $event->event_category,
                ];
            });

        return response()->json([
            'categories' => $categories,
            'events' => $events
        ], 200);
    } catch (Exception $e) {
        Log::error('Error fetching dropdown data: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch dropdown data.'], 500);
    }
}

    /**
     * Display the specified event record.
     */
    public function show($event_id)
    {
        try {
            $event = Event::select($this->getSelectableFields())->find($event_id);
            if (!$event) {
                return response()->json(['message' => 'Event record not found'], 404);
            }
            return response()->json(['event' => $event], 200);
        } catch (Exception $e) {
            Log::error('Error fetching event record for ID ' . $event_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch event record.'], 500);
        }
    }

    /**
     * Update the specified event record using POST.
     */
    public function update(Request $request, $event_id)
    {
        $event = Event::find($event_id);
        if (!$event) {
            return response()->json(['message' => 'Event record not found'], 404);
        }

        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('img_file') && $request->file('img_file')->isValid()) {
                if ($event->img_file && File::exists(public_path($event->img_file))) {
                    File::delete(public_path($event->img_file));
                }
                $file = $request->file('img_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/events'), $fileName);
                $data['img_file'] = 'uploads/events/' . $fileName;
            }

            $event->update($data);
            return response()->json(['message' => 'Event record updated successfully.', 'event' => $event->fresh()], 200);
        } catch (Exception $e) {
            Log::error('Error updating event record for ID ' . $event_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update event record.'], 500);
        }
    }

    /**
     * Remove the specified event record.
     */
    public function destroy($event_id)
    {
        try {
            $event = Event::find($event_id);
            if (!$event) {
                return response()->json(['message' => 'Event record not found'], 404);
            }

            if ($event->img_file && File::exists(public_path($event->img_file))) {
                File::delete(public_path($event->img_file));
            }
            
            $event->delete();
            return response()->json(['message' => 'Event record deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error('Error deleting event record for ID ' . $event_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete event record.'], 500);
        }
    }
}