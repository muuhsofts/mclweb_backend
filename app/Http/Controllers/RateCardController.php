<?php
// app/Http/Controllers/RateCardController.php

namespace App\Http\Controllers;

use App\Models\RateCard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class RateCardController extends Controller
{
    /**
     * Allowed MIME types for rate card files.
     */
    private const ALLOWED_MIMES = [
        // Images
        'jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp', 'svg',
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
    ];

    /**
     * Allowed file extensions (extra safety layer).
     */
    private const ALLOWED_EXTENSIONS = [
        'jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
    ];

    /**
     * The path for storing uploaded rate card files.
     */
    private const FILE_UPLOAD_PATH = 'uploads/rate_cards';

    public function __construct()
    {
        // Protect all methods except public-facing ones.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'download']);
    }

    /**
     * Display a listing of rate cards.
     */
    public function index()
    {
        return RateCard::orderBy('rate_card_id', 'desc')->get();
    }

    /**
 * Get the latest rate card (public).
 * Returns the most recent rate card by rate_card_id.
 */
public function latest()
{
    $rateCard = RateCard::orderBy('rate_card_id', 'desc')->first();

    if (!$rateCard) {
        return response()->json([
            'message' => 'No rate cards available.',
            'rate_card' => null,
        ], Response::HTTP_OK);
    }

    return response()->json([
        'rate_card' => $rateCard,
    ], Response::HTTP_OK);
}

    /**
     * Store a newly created rate card.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'headline'       => 'nullable|string|max:255',
            'rate_card_file' => 'required|file|mimes:' . implode(',', self::ALLOWED_MIMES) . '|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            // Ensure upload directory exists
            $uploadDir = public_path(self::FILE_UPLOAD_PATH);
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            // Handle file upload
            if ($request->hasFile('rate_card_file')) {
                $file = $request->file('rate_card_file');

                // Extra validation: check extension
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                    return response()->json([
                        'errors' => ['rate_card_file' => ['The file type is not allowed.']]
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
                $file->move($uploadDir, $fileName);
                $validatedData['rate_card_file'] = self::FILE_UPLOAD_PATH . '/' . $fileName;
            }

            $rateCard = RateCard::create($validatedData);

            return response()->json([
                'message'   => 'Rate card created successfully.',
                'rate_card' => $rateCard,
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            Log::error('Error creating rate card: ' . $e->getMessage());
            return response()->json([
                'error'   => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified rate card.
     */
    public function show($rate_card_id)
    {
        return RateCard::findOrFail($rate_card_id);
    }

    /**
     * Update the specified rate card.
     */
    public function update(Request $request, $rate_card_id)
    {
        $rateCard = RateCard::findOrFail($rate_card_id);

        $validator = Validator::make($request->all(), [
            'headline'       => 'nullable|string|max:255',
            'rate_card_file' => 'nullable|file|mimes:' . implode(',', self::ALLOWED_MIMES) . '|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('rate_card_file')) {
                // Delete old file
                if ($rateCard->rate_card_file) {
                    File::delete(public_path($rateCard->rate_card_file));
                }

                $file = $request->file('rate_card_file');

                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                    return response()->json([
                        'errors' => ['rate_card_file' => ['The file type is not allowed.']]
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
                $file->move(public_path(self::FILE_UPLOAD_PATH), $fileName);
                $validatedData['rate_card_file'] = self::FILE_UPLOAD_PATH . '/' . $fileName;

            } elseif (array_key_exists('rate_card_file', $validatedData) && $validatedData['rate_card_file'] === null) {
                // Explicitly remove file by sending null
                if ($rateCard->rate_card_file) {
                    File::delete(public_path($rateCard->rate_card_file));
                }
                $validatedData['rate_card_file'] = null;
            }

            $rateCard->update($validatedData);

            return response()->json([
                'message'   => 'Rate card updated successfully.',
                'rate_card' => $rateCard->fresh(),
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error("Error updating rate card ID {$rate_card_id}: " . $e->getMessage());
            return response()->json([
                'error'   => 'An unexpected error occurred during the update.',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified rate card.
     */
    public function destroy($rate_card_id)
    {
        $rateCard = RateCard::findOrFail($rate_card_id);

        try {
            if ($rateCard->rate_card_file) {
                File::delete(public_path($rateCard->rate_card_file));
            }

            $rateCard->delete();

            return response()->noContent();

        } catch (Exception $e) {
            Log::error("Error deleting rate card ID {$rate_card_id}: " . $e->getMessage());
            return response()->json([
                'error'   => 'An unexpected error occurred during deletion.',
                'details' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download the rate card file.
     */
    public function download($rate_card_id)
    {
        $rateCard = RateCard::findOrFail($rate_card_id);

        $filePath = public_path($rateCard->rate_card_file);

        if (!$rateCard->rate_card_file || !File::exists($filePath)) {
            return response()->json([
                'error' => 'File not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Use the stored file name (original name was captured during upload)
        // We'll extract a friendly name for the download
        $originalName = $rateCard->headline
            ? preg_replace('/[^A-Za-z0-9._-]/', '_', $rateCard->headline) . '.' . pathinfo($filePath, PATHINFO_EXTENSION)
            : basename($filePath);

        return response()->download($filePath, $originalName);
    }
}