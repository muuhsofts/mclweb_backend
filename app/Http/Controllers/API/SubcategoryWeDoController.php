<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubcategoryWeDo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class SubcategoryWeDoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        $subcategories = SubcategoryWeDo::with('whatWeDo')->latest()->get();
        return response()->json(['subcategories' => $subcategories], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'what_we_do_id' => 'required',
            'subcategory' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'img_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'web_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except('img_url');

        if ($request->hasFile('img_url')) {
            $file = $request->file('img_url');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/subcategories'), $filename);
            $data['img_url'] = 'uploads/subcategories/' . $filename;
        }

        $subcategory = SubcategoryWeDo::create($data);
        return response()->json(['message' => 'Subcategory created successfully', 'subcategory' => $subcategory->load('whatWeDo')], 201);
    }

    public function show(SubcategoryWeDo $subcategoryWeDo)
    {
        return response()->json(['subcategory' => $subcategoryWeDo->load('whatWeDo')], 200);
    }

    public function update(Request $request, SubcategoryWeDo $subcategoryWeDo)
    {
        $validator = Validator::make($request->all(), [
            'what_we_do_id' => 'required',
            'subcategory' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'img_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'web_url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except('img_url');

        if ($request->hasFile('img_url')) {
            if ($subcategoryWeDo->img_url && File::exists(public_path($subcategoryWeDo->img_url))) {
                File::delete(public_path($subcategoryWeDo->img_url));
            }
            $file = $request->file('img_url');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/subcategories'), $filename);
            $data['img_url'] = 'uploads/subcategories/' . $filename;
        }

        $subcategoryWeDo->update($data);
        return response()->json(['message' => 'Subcategory updated successfully', 'subcategory' => $subcategoryWeDo->load('whatWeDo')], 200);
    }

    public function destroy(SubcategoryWeDo $subcategoryWeDo)
    {
        if ($subcategoryWeDo->img_url && File::exists(public_path($subcategoryWeDo->img_url))) {
            File::delete(public_path($subcategoryWeDo->img_url));
        }
        $subcategoryWeDo->delete();
        return response()->json(['message' => 'Subcategory deleted successfully'], 200);
    }
}