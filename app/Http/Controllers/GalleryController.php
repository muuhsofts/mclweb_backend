<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class GalleryController extends Controller
{
    private const UPLOAD_PATH = 'uploads/gallery';

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        try {
            $data = Gallery::orderByDesc('id')->get();
            return response()->json($data, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20000',
            'file_type' => 'required|in:image,video'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('file')) {
                $file = $request->file('file');

                $name = time().'_'.$file->getClientOriginalName();
                $file->move(public_path(self::UPLOAD_PATH), $name);

                $data['file_path'] = self::UPLOAD_PATH.'/'.$name;
            }

            $gallery = Gallery::create($data);

            return response()->json($gallery, 201);

        } catch (Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function show($id)
    {
        $item = Gallery::find($id);

        if (!$item) {
            return response()->json(['message'=>'Not found'],404);
        }

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $gallery = Gallery::find($id);

        if (!$gallery) {
            return response()->json(['message'=>'Not found'],404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20000'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(),422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('file')) {

                // delete old
                if ($gallery->file_path && File::exists(public_path($gallery->file_path))) {
                    File::delete(public_path($gallery->file_path));
                }

                $file = $request->file('file');
                $name = time().'_'.$file->getClientOriginalName();
                $file->move(public_path(self::UPLOAD_PATH), $name);

                $data['file_path'] = self::UPLOAD_PATH.'/'.$name;
            }

            $gallery->update($data);

            return response()->json($gallery);

        } catch (Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    public function destroy($id)
    {
        $gallery = Gallery::find($id);

        if (!$gallery) {
            return response()->json(['message'=>'Not found'],404);
        }

        if ($gallery->file_path && File::exists(public_path($gallery->file_path))) {
            File::delete(public_path($gallery->file_path));
        }

        $gallery->delete();

        return response()->json(['message'=>'Deleted']);
    }

    
    public function fetchAllGallery()
{
    try {
        $galleries = \App\Models\Gallery::orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $galleries
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch galleries',
            'error' => $e->getMessage()
        ], 500);
    }
}
}