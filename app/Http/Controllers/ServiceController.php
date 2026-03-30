<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Set up authentication middleware for the controller.
     */
    public function __construct()
    {
        // Require authentication for all methods except public-facing ones.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'latestservice', 'allService']);
    }

    /**
     * Display a listing of all services.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $services = Service::orderBy('service_id', 'desc')->get();
            return response()->json(['services' => $services], 200);
        } catch (Exception $e) {
            Log::error('Error fetching service records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch service records.'], 500);
        }
    }

    /**
     * Count the total number of service records.
     *
     * @return JsonResponse
     */
    public function countServices(): JsonResponse
    {
        try {
            $count = Service::count();
            return response()->json(['count_services' => $count], 200);
        } catch (Exception $e) {
            Log::error('Error counting service records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to count service records.'], 500);
        }
    }

    /**
     * Display all services (alias for index).
     *
     * @return JsonResponse
     */
    public function allService(): JsonResponse
    {
        try {
            $services = Service::orderBy('service_id', 'desc')->get();
            return response()->json(['services' => $services], 200);
        } catch (Exception $e) {
            Log::error('Error fetching service records: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch service records.'], 500);
        }
    }

    /**
     * Display the latest created service.
     *
     * @return JsonResponse
     */
    public function latestservice(): JsonResponse
    {
        try {
            $latestService = Service::latest('created_at')->first();
            if (!$latestService) {
                return response()->json(['message' => 'No service record found'], 404);
            }
            return response()->json(['service' => $latestService], 200);
        } catch (Exception $e) {
            Log::error('Error fetching latest service record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch latest service record.'], 500);
        }
    }

    /**
     * Store a newly created service in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('Store request received', ['files' => $request->allFiles()]);

        $validator = Validator::make($request->all(), [
            'service_category' => 'required|string|max:255',
            'service_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'url_link' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            Log::error('Validation errors: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('service_image') && $request->file('service_image')->isValid()) {
                $data['service_img'] = $this->handleImageUpload($request->file('service_image'));
            }

            $service = Service::create($data);
            return response()->json(['message' => 'Service record created successfully', 'service' => $service], 201);
        } catch (Exception $e) {
            Log::error('Error creating service record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create service record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified service.
     *
     * @param int $service_id
     * @return JsonResponse
     */
    public function show($service_id): JsonResponse
    {
        $service = Service::find($service_id);
        if (!$service) {
            return response()->json(['message' => 'Service record not found'], 404);
        }
        return response()->json(['service' => $service], 200);
    }

    /**
     * Update the specified service in storage.
     *
     * @param Request $request
     * @param int $service_id
     * @return JsonResponse
     */
    public function update(Request $request, $service_id): JsonResponse
    {
        Log::info('Update request received for service_id: ' . $service_id, ['files' => $request->allFiles()]);

        $service = Service::find($service_id);
        if (!$service) {
            return response()->json(['message' => 'Service record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'service_category' => 'sometimes|required|string|max:255',
            'service_image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'url_link' => 'sometimes|nullable|url|max:255',
            'description' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation errors: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('service_image') && $request->file('service_image')->isValid()) {
                $data['service_img'] = $this->handleImageUpload($request->file('service_image'), $service->service_img);
            }

            $service->update($data);
            return response()->json(['message' => 'Service record updated successfully', 'service' => $service->fresh()], 200);
        } catch (Exception $e) {
            Log::error('Error updating service record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update service record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified service from storage.
     *
     * @param int $service_id
     * @return JsonResponse
     */
    public function destroy($service_id): JsonResponse
    {
        $service = Service::find($service_id);
        if (!$service) {
            return response()->json(['message' => 'Service record not found'], 404);
        }

        try {
            $this->deleteImage($service->service_img);
            $service->delete();
            return response()->json(['message' => 'Service record deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error('Error deleting service record: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete service record.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Handles the file upload process.
     *
     * @param UploadedFile $image The uploaded file instance.
     * @param string|null $oldImagePath The path to the old image to be deleted.
     * @return string The public path to the newly saved image.
     * @throws Exception If the file cannot be moved.
     */
    private function handleImageUpload(UploadedFile $image, ?string $oldImagePath = null): string
    {
        Log::info('Starting image upload process for file: ' . $image->getClientOriginalName());

        // Delete the old image if it exists.
        if ($oldImagePath) {
            $this->deleteImage($oldImagePath);
        }

        // Generate a unique name for the image.
        $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());

        // Define the upload path within the public directory.
        $uploadPath = 'uploads/service_images';
        $fullPath = public_path($uploadPath);

        // Create the directory if it doesn't exist.
        if (!File::exists($fullPath)) {
            Log::info('Creating directory: ' . $fullPath);
            File::makeDirectory($fullPath, 0755, true);
        }

        // Move the file to the public/uploads/service_images directory.
        if (!$image->move($fullPath, $imageName)) {
            Log::error('Failed to move image to: ' . $fullPath . '/' . $imageName);
            throw new Exception('Failed to move uploaded image to ' . $fullPath);
        }

        Log::info('Service image uploaded: ' . $uploadPath . '/' . $imageName);
        return $uploadPath . '/' . $imageName;
    }

    /**
     * Deletes an image from the public path if it exists.
     *
     * @param string|null $imagePath The database path to the image.
     */
    private function deleteImage(?string $imagePath): void
    {
        if ($imagePath && File::exists(public_path($imagePath))) {
            File::delete(public_path($imagePath));
            Log::info('Deleted service image: ' . $imagePath);
        }
    }
}