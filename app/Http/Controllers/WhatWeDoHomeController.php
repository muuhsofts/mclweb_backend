<?php

namespace App\Http\Controllers;

use App\Models\WhatWeDoHome;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class WhatWeDoHomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'whatWeDoHomeSlider']);
    }

    public function index()
    {
        try {
            \Log::info('Fetching all what we do home sliders');
            $sliders = WhatWeDoHome::orderBy('what_we_do_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching what we do home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch what we do home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function whatWeDoHomeSlider()
    {
        try {
            \Log::info('Fetching all what we do home sliders for public view');
            $sliders = WhatWeDoHome::orderBy('what_we_do_id', 'desc')->get();
            \Log::info('Retrieved sliders: ', ['count' => $sliders->count()]);
            return response()->json($sliders, Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error fetching what we do home sliders: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch what we do home sliders', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        \Log::info('What we do home slider store request data: ', $request->all());

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:1000',
            'heading' => 'required|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for what we do home slider store: ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = public_path('uploads/what_we_do_homes');
                if (!File::isDirectory($destinationFolder)) {
                    File::makeDirectory($destinationFolder, 0755, true);
                }

                $image->move($destinationFolder, $imageName);
                $validatedData['home_img'] = 'uploads/what_we_do_homes/' . $imageName;
                \Log::info('What we do home slider image uploaded: ' . $validatedData['home_img']);
            }

            $slider = WhatWeDoHome::create($validatedData);
            \Log::info('What we do home slider created successfully: ', $slider->toArray());
            return response()->json(['message' => 'What we do home slider created successfully', 'slider' => $slider], Response::HTTP_CREATED);
        } catch (Exception $e) {
            \Log::error('Error creating what we do home slider: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create what we do home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($what_we_do_id)
    {
        \Log::info('Fetching what we do home slider with ID: ' . $what_we_do_id);
        try {
            $slider = WhatWeDoHome::findOrFail($what_we_do_id);
            \Log::info('What we do home slider found: ', $slider->toArray());
            return response()->json($slider, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('What we do home slider not found for ID: ' . $what_we_do_id);
            return response()->json(['error' => 'What we do home slider not found'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            \Log::error('Error fetching what we do home slider for ID ' . $what_we_do_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to retrieve what we do home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $what_we_do_id)
    {
        \Log::info('What we do home slider update request data for ID ' . $what_we_do_id . ': ', $request->all());

        $slider = WhatWeDoHome::find($what_we_do_id);
        if (!$slider) {
            \Log::warning('What we do home slider not found for update, ID: ' . $what_we_do_id);
            return response()->json(['error' => 'What we do home slider not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:1000',
            'heading' => 'required|string|max:255',
            'home_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            \Log::warning('Validation failed for what we do home slider update ID ' . $what_we_do_id . ': ', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $validatedData = $validator->validated();

            if ($request->hasFile('home_img')) {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted old what we do home slider image: ' . $oldImagePath);
                    }
                }

                $image = $request->file('home_img');
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $sanitizedName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $originalName);
                $imageName = time() . '_' . $sanitizedName . '.' . $image->getClientOriginalExtension();

                $destinationFolder = public_path('uploads/what_we_do_homes');
                if (!File::isDirectory($destinationFolder)) {
                    File::makeDirectory($destinationFolder, 0755, true);
                }

                $image->move($destinationFolder, $imageName);
                $validatedData['home_img'] = 'uploads/what_we_do_homes/' . $imageName;
                \Log::info('New what we do home slider image uploaded: ' . $validatedData['home_img']);
            } elseif ($request->has('home_img') && $request->input('home_img') === '') {
                if ($slider->home_img) {
                    $oldImagePath = public_path($slider->home_img);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                        \Log::info('Deleted existing what we do home slider image (empty input): ' . $oldImagePath);
                    }
                    $validatedData['home_img'] = null;
                }
            } else {
                unset($validatedData['home_img']);
            }

            $slider->fill(array_filter($validatedData, fn($value) => $value !== null))->save();
            \Log::info('What we do home slider updated successfully for ID: ' . $what_we_do_id);

            return response()->json([
                'message' => 'What we do home slider updated successfully',
                'slider' => $slider->fresh()
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error updating what we do home slider for ID ' . $what_we_do_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update what we do home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($what_we_do_id)
    {
        $slider = WhatWeDoHome::find($what_we_do_id);
        if (!$slider) {
            \Log::warning('What we do home slider not found for deletion, ID: ' . $what_we_do_id);
            return response()->json(['error' => 'What we do home slider not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            if ($slider->home_img) {
                $imagePath = public_path($slider->home_img);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                    \Log::info('Deleted what we do home slider image: ' . $imagePath);
                }
            }

            $slider->delete();
            \Log::info('What we do home slider deleted successfully for ID: ' . $what_we_do_id);
            return response()->json(['message' => 'What we do home slider deleted successfully'], Response::HTTP_OK);
        } catch (Exception $e) {
            \Log::error('Error deleting what we do home slider for ID ' . $what_we_do_id . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete what we do home slider', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}