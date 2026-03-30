<?php

namespace App\Http\Controllers;

use App\Models\SubEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class SubEventController extends Controller
{
    /**
     * Get selectable fields for sub-events.
     */
    private function getSelectableFields()
    {
        return [
            'subevent_id',
            'event_id',
            'sub_category',
            'description',
            'img_file',
            'video_link',
            'created_at',
        ];
    }

    /**
     * Display a paginated listing of sub-events.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);
            $subEventRecords = SubEvent::select($this->getSelectableFields())
                ->with(['event' => function ($query) {
                    $query->select('event_id', 'event_category');
                }])
                ->orderBy('subevent_id', 'desc')
                ->paginate($perPage);
            return response()->json(['sub_events' => $subEventRecords], 200);
        } catch (Exception $e) {
            Log::error('Error fetching sub-event records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sub-event records.'], 500);
        }
    }

    /**
     * Display all sub-events without pagination.
     */
    public function allEvents()
    {
        try {
            $subEventRecords = SubEvent::select($this->getSelectableFields())
                ->with(['event' => function ($query) {
                    $query->select('event_id', 'event_category');
                }])
                ->orderBy('subevent_id', 'desc')
                ->get();
            return response()->json(['sub_events' => $subEventRecords], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all sub-event records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch all sub-event records.'], 500);
        }
    }

    /**
     * Get the total count of sub-events.
     */
    public function countEvents()
    {
        try {
            $count = SubEvent::count();
            return response()->json(['count' => $count], 200);
        } catch (Exception $e) {
            Log::error('Error fetching sub-event count: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sub-event count.'], 500);
        }
    }

    /**
     * Get the latest sub-event.
     */
    public function latestEvent()
    {
        try {
            $latestSubEvent = SubEvent::select($this->getSelectableFields())
                ->with(['event' => function ($query) {
                    $query->select('event_id', 'event_category');
                }])
                ->orderBy('subevent_id', 'desc')
                ->first();
            return response()->json(['sub_event' => $latestSubEvent], 200);
        } catch (Exception $e) {
            Log::error('Error fetching latest sub-event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest sub-event.'], 500);
        }
    }

    /**
     * Display a specific sub-event.
     */
    public function show($subevent_id)
    {
        try {
            $subEvent = SubEvent::select($this->getSelectableFields())
                ->with(['event' => function ($query) {
                    $query->select('event_id', 'event_category');
                }])
                ->where('subevent_id', $subevent_id)
                ->firstOrFail();
            return response()->json(['sub_event' => $subEvent], 200);
        } catch (Exception $e) {
            Log::error('Error fetching sub-event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sub-event.'], 404);
        }
    }

    /**
     * Store a newly created sub-event in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'event_id' => 'required|integer|exists:events,event_id',
                'sub_category' => 'required|string|max:255',
                'description' => 'nullable|string|max:100000',
                'img_file' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
                'video_link' => 'nullable|url',
            ]);

            $data = $validated;

            if ($request->hasFile('img_file')) {
                $file = $request->file('img_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('uploads/sub_events');
                // Ensure the directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $fileName);
                $data['img_file'] = 'uploads/sub_events/' . $fileName;
            }

            $subEvent = SubEvent::create($data);

            return response()->json([
                'message' => 'Sub-event created successfully!',
                'sub_event' => $subEvent->load(['event' => function ($query) {
                    $query->select('event_id', 'event_category');
                }])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating sub-event: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create sub-event.',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified sub-event in storage.
     */
    public function update(Request $request, $subevent_id)
    {
        try {
            $subEvent = SubEvent::findOrFail($subevent_id);

            $validated = $request->validate([
                'event_id' => 'required|integer|exists:events,event_id',
                'sub_category' => 'required|string|max:255',
                'description' => 'nullable|string|max:100000',
                'img_file' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
                'video_link' => 'nullable|url',
            ]);

            $data = $validated;

            if ($request->hasFile('img_file')) {
                // Delete the old image if it exists
                if ($subEvent->img_file && file_exists(public_path($subEvent->img_file))) {
                    unlink(public_path($subEvent->img_file));
                }
                $file = $request->file('img_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('uploads/sub_events');
                // Ensure the directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $fileName);
                $data['img_file'] = 'uploads/sub_events/' . $fileName;
            } else {
                $data['img_file'] = $subEvent->img_file;
            }

            $subEvent->update($data);

            return response()->json([
                'message' => 'Sub-event updated successfully!',
                'sub_event' => $subEvent->load(['event' => function ($query) {
                    $query->select('event_id', 'event_category');
                }])
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating sub-event: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update sub-event.',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified sub-event from storage.
     */
    public function destroy($subevent_id)
    {
        try {
            $subEvent = SubEvent::findOrFail($subevent_id);

            if ($subEvent->img_file && file_exists(public_path($subEvent->img_file))) {
                unlink(public_path($subEvent->img_file));
            }

            $subEvent->delete();

            return response()->json(['message' => 'Sub-event deleted successfully!'], 200);
        } catch (Exception $e) {
            Log::error('Error deleting sub-event: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete sub-event.'], 500);
        }
    }
}